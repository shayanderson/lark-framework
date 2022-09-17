<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Database\Constraint;

use Closure;
use Lark\Database;
use Lark\Database\Connection;
use Lark\Database\Constraint;
use Lark\Database\Convert;
use Lark\Map;
use Lark\Map\Path as MapPath;

/**
 * Database model schema constraint ref fk
 *
 * @author Shay Anderson
 */
class RefFk extends Constraint
{
	/**
	 * Foreign field name
	 *
	 * @var string
	 */
	private string $foreignField;

	/**
	 * Local field array of IDs flag like [id1, id2]
	 *
	 * @var boolean
	 */
	private bool $isLocalFieldArrayOfIds = false;

	/**
	 * Local field array of field IDs flag like [[id => ...], [id => ...]]
	 *
	 * @var boolean
	 */
	private bool $isLocalFieldArrayOfFieldIds = false;

	/**
	 * Local field is nullable flag
	 *
	 * @var boolean
	 */
	private bool $isLocalFieldNullable = false;

	/**
	 * Local field name
	 *
	 * @var string
	 */
	private string $localField;

	/**
	 * Local field original name
	 *
	 * @var string
	 */
	private string $localFieldOrig;

	/**
	 * Init
	 *
	 * @param string $collection
	 * @param string $localField
	 * @param string $foreignField
	 */
	public function __construct(string $collection, string $localField, string $foreignField)
	{
		parent::__construct($collection);
		$this->localField = $localField;
		$this->localFieldOrig = $localField;
		$this->foreignField = $foreignField === 'id' ? '_id' : $foreignField;

		if (!$this->localField)
		{
			throw new DatabaseConstraintException(
				'Foreign key constraint local field cannot be empty',
				$this->context()
			);
		}

		if (!$this->foreignField)
		{
			throw new DatabaseConstraintException(
				'Foreign key constraint foreign field cannot be empty',
				$this->context()
			);
		}

		// nullable$field
		if (substr($this->localField, 0, 9) === 'nullable$')
		{
			$this->isLocalFieldNullable = true;
			$this->localField = substr($this->localField, 9);
		}

		// lField.$
		if (substr($this->localField, -2) == '.$')
		{
			$this->isLocalFieldArrayOfIds = true;
			$this->localField = substr($this->localField, 0, -2);
		}
		// lField.$.field
		else if (strpos($this->localField, '.$.') !== false)
		{
			$this->isLocalFieldArrayOfFieldIds = true;
			$this->localField = str_replace('$.', '', $this->localField);
		}
	}

	/**
	 * Debug context getter
	 *
	 * @return array
	 */
	private function context(): array
	{
		return [
			'localField' => $this->localFieldOrig,
			'foreignField' => $this->foreignField
		];
	}

	/**
	 * Convert object to array (recursive)
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private static function &objectToArray($value)
	{
		if (!is_array($value) && !is_object($value))
		{
			return $value;
		}

		$r = [];

		foreach ((array)$value as $k => $v)
		{
			$r[$k] = self::objectToArray($v);
		}

		return $r;
	}

	/**
	 * Verify FK constraint
	 *
	 * @param Database $db
	 * @param array $documents
	 * @param array $options
	 * @param Closure $debugCallback
	 * @return void
	 */
	public function verify(
		Database $db,
		array $documents,
		array $options,
		Closure $debugCallback
	): void
	{
		// IDs to verify
		$ids = [];

		$fn_id = function ($id) use (&$ids)
		{
			if (is_array($id))
			{
				throw new DatabaseConstraintException(
					'Field ID cannot be an array, use "field.$" instead'
				);
			}

			if (!$this->isLocalFieldNullable)
			{
				$ids[$id] = $id;
				return;
			}

			if ($id !== null) // nullable
			{
				$ids[$id] = $id;
			}
		};

		// lField.$.field => fField
		if ($this->isLocalFieldArrayOfFieldIds)
		{
			$fn_doc_ids = function (array &$doc) use (&$fn_id)
			{
				$r = &$doc;
				$last = false;

				foreach (explode('.', $this->localFieldOrig) as $f)
				{
					if ($f == '$')
					{
						// next field is local nested field
						$last = true;
						continue;
					}

					if ($last)
					{
						// local nested field, loop and extract nested field value
						if (is_array($r))
						{
							foreach ($r as $v)
							{
								if (is_array($v) && array_key_exists($f, $v))
								{
									$fn_id($v[$f]);
								}
							}
						}
						break;
					}

					// keep building nested array
					if (is_array($r) && array_key_exists($f, $r))
					{
						$r = &$r[$f];
					}
				}
			};

			foreach ($documents as $k => $doc)
			{
				// always convert to array
				$doc = self::objectToArray($doc);

				// check if local field exists
				if (!MapPath::has(
					$doc,
					// "f1.f2.$.id" => "f1.f2"
					substr($this->localFieldOrig, 0, strpos($this->localFieldOrig, '.$.'))
				))
				{
					continue;
				}

				$fn_doc_ids($doc);
			}
		}
		// lField => fField --OR-- lField.$ => fField
		else
		{
			foreach ($documents as $k => $doc)
			{
				// always convert to array
				$documents[$k] = self::objectToArray($doc);

				// check if local field exists
				if (MapPath::has($documents[$k], $this->localField))
				{
					// lField.$ => fField
					if ($this->isLocalFieldArrayOfIds)
					{
						foreach (MapPath::get($documents[$k], $this->localField) ?? [] as $id)
						{
							$fn_id($id);
						}
					}
					// lField => fField
					else
					{
						$fn_id(
							MapPath::get($documents[$k], $this->localField)
						);
					}
				}
			}
		}

		if (!$ids)
		{
			$debugCallback(
				0,
				__METHOD__,
				['message' => 'No IDs to verify'] +
					$this->context() + [
						'idCount' => count($ids)
					]
			);
			return; // nothing to verify
		}

		// rm duplicate IDs
		$ids = array_unique($ids);

		// values only
		$ids = array_values($ids);

		$dbForeign = Connection::factory(
			$db->connection()->getId(),
			$db->getDatabaseName(),
			$this->getCollection()
		);

		$filter = [
			$this->foreignField => [
				'$in' => Convert::idsToObjectIds($ids)
			]
		];

		$count = $dbForeign->collection()->countDocuments($filter, $options);

		$debugCallback(
			$count,
			__METHOD__,
			[
				'collectionLocal' => $db->getCollectionName()
			] + $this->context() + [
				'filter' => $filter,
				'count' => $count,
				'idCount' => count($ids)
			]
		);

		if ($count !== count($ids))
		{
			throw new DatabaseConstraintException(
				'Failed to insert or update document(s), foreign key constraint failed for "'
					. $this->localFieldOrig . '"',
				[
					'collectionLocal' => $db->getCollectionName()
				] + $this->context() + [
					'filter' => $filter,
					'ids' => $ids,
					'count' => $count,
					'idCount' => count($ids)
				]
			);
		}
	}
}
