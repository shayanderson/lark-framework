<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Database\Constraint;

use Closure;
use Lark\Database;
use Lark\Database\Connection;
use Lark\Database\Constraint;

/**
 * Database model schema constraint ref delete
 *
 * @author Shay Anderson
 */
class RefDelete extends Constraint
{
	/**
	 * Foreign field names
	 *
	 * @var array
	 */
	private array $foreignFields;

	/**
	 * Init
	 *
	 * @param string $collection
	 * @param array $foreignFields
	 */
	public function __construct(string $collection, array $foreignFields)
	{
		parent::__construct($collection);
		$this->foreignFields = $foreignFields;

		if (!$this->foreignFields)
		{
			throw new DatabaseConstraintException(
				'Ref delete constraint foreign fields cannot be empty',
				[
					'collection' => $collection,
					'foreignFields' => $foreignFields
				]
			);
		}
	}

	/**
	 * Delete
	 *
	 * @param Database $db
	 * @param array $ids
	 * @param array $options
	 * @param Closure $debugCallback
	 * @return integer Affected
	 */
	public function delete(Database $db, array $ids, array $options, Closure $debugCallback): int
	{
		$aff = 0;

		$dbForeign = Connection::factory(
			$db->connection()->getId(),
			$db->getDatabaseName(),
			$this->getCollection()
		);

		foreach ($this->foreignFields as $foreignField)
		{
			// field.$ for in array of IDs
			if (substr($foreignField, -2) == '.$')
			{
				// field without '.$'
				$ff = substr($foreignField, 0, -2);

				// all multi option
				$options += ['multi' => true];

				$filter = [
					$ff => [
						'$in' => $ids
					]
				];

				$update = [
					'$pullAll' => [$ff => $ids]
				];

				$res = $dbForeign->collection()->updateMany($filter, $update, $options);

				$resAff = $res ? $res->getModifiedCount() : 0;

				$aff += $debugCallback(
					$resAff,
					__METHOD__,
					[
						'ids' => $ids,
						'foreignField' => $foreignField,
						'filter' => $filter,
						'update' => $update,
						'options' => $options
					]
				);
			}
			// field.$.field for object.field in array
			else if (strpos($foreignField, '.$.') !== false)
			{
				$filter = [
					str_replace('$.', '', $foreignField) => [
						'$in' => $ids
					]
				];

				$update = [];

				// build [f1 => [f2 => [$in => $ids]]]
				$fn_update = function (array $foreignFieldParts) use (&$update, &$ids)
				{
					$last = false;
					foreach ($foreignFieldParts as $f)
					{
						if ($f == '$')
						{
							$last = true;
							continue;
						}

						if ($last)
						{
							$update[$f] = ['$in' => $ids];
						}
						else
						{
							$update = &$update[$f];
						}
					}
				};

				$ffParts = explode('.', $foreignField);

				if (count($ffParts) > 3)
				{
					// nested object, like:
					// { $pull: { "f1.f2": { id: { $id: [...] } } } }
					$tmp = '';
					foreach ($ffParts as $k => $part)
					{
						if ($part === '$')
						{
							break;
						}

						$tmp .= (!$tmp ? null : '.') . $part;
						unset($ffParts[$k]);
					}

					array_unshift($ffParts, $tmp); // prepend "f1.f2" to like ["$", "id"]
				}

				$fn_update($ffParts);

				// $fn_update(
				// 	array_filter(explode('.', $foreignField))
				// );
				// count(explode('.', $foreignField)) > 3

				if (!$update)
				{
					throw new DatabaseConstraintException(
						'Failed to generate update for ref delete constraint',
						[
							'foreignField' => $foreignField
						]
					);
				}

				$update = ['$pull' => $update];

				// all multi option
				$options += ['multi' => true];

				try
				{
					$res = $dbForeign->collection()->updateMany($filter, $update, $options);
				}
				catch (\Throwable $th)
				{
					p(__METHOD__, $filter, $update, $foreignField);
					throw $th;
				}

				$resAff = $res ? $res->getModifiedCount() : 0;

				$aff += $debugCallback(
					$resAff,
					__METHOD__,
					[
						'aff' => $resAff,
						'ids' => $ids,
						'foreignField' => $foreignField,
						'filter' => $filter,
						'update' => $update,
						'options' => $options
					]
				);
			}
			// default filter [foreignField => [$in => [...]]]
			else
			{
				$aff += $debugCallback(
					$dbForeign->delete(
						[$foreignField => ['$in' => $ids]],
						$options
					),
					__METHOD__,
					[
						'ids' => $ids,
						'foreignField' => $foreignField,
						'options' => $options
					]
				);
			}
		}

		return $aff;
	}
}
