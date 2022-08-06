<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Database;

use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Cursor;
use MongoDB\Model\BSONDocument;
use Traversable;

/**
 * Database convert
 *
 * @author Shay Anderson
 */
class Convert
{
	/**
	 * Convert MongoDB BSON doc to array
	 *
	 * @param BSONDocument|null $doc
	 * @return array|null
	 */
	public static function &bsonDocToArray(?BSONDocument $doc): ?array
	{
		if (!$doc)
		{
			$doc = null;
			return $doc;
		}

		$doc = &self::iteratorToArrayRecursive($doc);

		// convert [_id => ObjectId] to [id => string]
		if (isset($doc['_id']) && $doc['_id'] instanceof ObjectId)
		{
			$doc = ['id' => $doc['_id']->__toString()] + $doc;
			unset($doc['_id']);
		}

		return $doc;
	}

	/**
	 * Convert MongoDB cursor to array
	 *
	 * @param Cursor $cursor
	 * @return array
	 */
	public static function &cursorToArray(Cursor $cursor): array
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
	public static function &idsToObjectIds(array &$ids): array
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
	 * Convert iterator to array recursively
	 *
	 * @param Traversable $iterator
	 * @return array
	 */
	private static function &iteratorToArrayRecursive(Traversable $iterator): array
	{
		$a = iterator_to_array($iterator);

		foreach ($a as $k => $v)
		{
			if (is_iterable($v))
			{
				$a[$k] = self::iteratorToArrayRecursive($v);
			}
		}

		return $a;
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
