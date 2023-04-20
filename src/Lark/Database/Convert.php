<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Database;

use MongoDB\BSON\Decimal128;
use MongoDB\BSON\Int64;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Timestamp;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Cursor;
use MongoDB\Model\BSONDocument;
use Traversable;
use stdClass;

/**
 * Database convert
 *
 * @author Shay Anderson
 */
class Convert
{
	/**
	 * Convert BSONDocument BSON objects to PHP types (recursively)
	 *
	 * @param array|stdClass $doc
	 * @param bool $root
	 * @return array|stdClass
	 */
	private static function &bsonDocBsonToPhp(
		array | stdClass $doc,
		bool $root = true
	): array | stdClass
	{
		$fn_set_prop = function ($k, $v) use (&$doc)
		{
			if (is_object($doc))
			{
				$doc->{$k} = $v;
			}
			else
			{
				$doc[$k] = $v;
			}
		};

		foreach ($doc as $k => $v)
		{
			if (is_array($v) || $v instanceof stdClass)
			{
				if ($v)
				{
					$fn_set_prop($k, self::bsonDocBsonToPhp($v, false));
				}
			}
			// convert BSON objects to PHP types
			else if (is_object($v))
			{
				// ObjectId to string
				if ($v instanceof ObjectId)
				{
					// "root._id" => "root.id"
					if ($k === '_id' && $root)
					{
						if (is_array($doc))
						{
							$doc = ['id' => $v->__toString()] + $doc;
							unset($doc['_id']);
						}
						else
						{
							throw new DatabaseException(
								'Unexpected object during BSON to PHP conversion'
							);
						}
					}
					else
					{
						$fn_set_prop($k, $v->__toString());
					}
				}
				// Timestamp to int
				else if ($v instanceof Timestamp)
				{
					$fn_set_prop($k, $v->getTimestamp());
				}
				// UTCDateTime to DateTime
				else if ($v instanceof UTCDateTime)
				{
					$fn_set_prop($k, $v->toDateTime());
				}
				// Decimal128 to float
				else if ($v instanceof Decimal128)
				{
					$fn_set_prop($k, floatval($v->__toString()));
				}
				// Int64 to int
				else if ($v instanceof Int64)
				{
					$fn_set_prop($k, intval($v->__toString()));
				}
			}
		}

		// "root._id" to "root.id" for string values
		if ($root && isset($doc['_id']))
		{
			$doc = ['id' => $doc['_id']] + $doc;
			unset($doc['_id']);
		}

		return $doc;
	}

	/**
	 * Convert MongoDB BSON doc to array
	 *
	 * @param BSONDocument|null $doc
	 * @return array|null
	 */
	public static function &bsonDocToArray(?BSONDocument $bsonDoc): ?array
	{
		$doc = null;

		if (!$bsonDoc)
		{
			return $doc;
		}

		// convert BSONDocument to array
		$doc = (array)\MongoDB\BSON\toPHP(\MongoDB\BSON\fromPHP($bsonDoc));

		return self::bsonDocBsonToPhp($doc);
	}

	/**
	 * Convert MongoDB cursor to array
	 *
	 * @param Cursor|Traversable $cursor
	 * @return array
	 */
	public static function &cursorToArray(Cursor|Traversable $cursor): array
	{
		$a = [];

		foreach ($cursor as $o)
		{
			$a[] = &self::bsonDocToArray($o);
		}

		return $a;
	}

	/**
	 * Convert IDs to object IDs
	 *
	 * @param array $ids
	 * @return array
	 */
	public static function &idsToObjectIds(array $ids): array
	{
		foreach ($ids as &$id)
		{
			$id = self::idToObjectId($id);
		}

		return $ids;
	}

	/**
	 * Convert ID to object ID
	 *
	 * @param string|null $id
	 * @return string|null|ObjectId
	 */
	public static function idToObjectId(?string $id)
	{
		if (!is_string($id) || strlen($id) !== 24)
		{
			return $id;
		}

		return new ObjectId($id);
	}

	/**
	 * Convert input ID to object ID
	 *
	 * @param array|object $document
	 * @return void
	 */
	public static function inputIdToObjectId(&$document): void
	{
		if (is_array($document))
		{
			if (isset($document['id']) || array_key_exists('id', $document))
			{
				$document['_id'] = self::idToObjectId((string)$document['id']);
				unset($document['id']);
			}
		}
		else if (is_object($document))
		{
			if (property_exists($document, 'id'))
			{
				$document->_id = self::idToObjectId((string)$document->id);
				unset($document->id);
			}
		}
	}

	/**
	 * Convert input ID to object ID for array
	 *
	 * @param array $documents
	 * @return void
	 */
	public static function inputIdToObjectIdArray(array &$documents): void
	{
		foreach ($documents as &$doc)
		{
			self::inputIdToObjectId($doc);
		}
	}

	/**
	 * Convert MongoDB BSON ObjectId to string
	 *
	 * @param ObjectId|string $objectId
	 * @return string|null
	 */
	public static function objectIdToString($objectId): ?string
	{
		if ($objectId instanceof ObjectId)
		{
			return $objectId->__toString();
		}

		if (is_scalar($objectId))
		{
			return (string)$objectId;
		}

		return null;
	}
}
