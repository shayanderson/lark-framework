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
 * Database model schema constraint ref clear
 *
 * @author Shay Anderson
 */
class RefClear extends Constraint
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
				'Ref clear constraint foreign fields cannot be empty',
				[
					'collection' => $collection,
					'foreignFields' => $foreignFields
				]
			);
		}
	}

	/**
	 * Clear
	 *
	 * @param Database $db
	 * @param array $ids
	 * @param array $options
	 * @param Closure $debugCallback
	 * @return integer Affected
	 */
	public function clear(Database $db, array $ids, array $options, Closure $debugCallback): int
	{
		$dbForeign = Connection::factory(
			$db->connection()->getId(),
			$db->getDatabaseName(),
			$this->getCollection()
		);

		$aff = 0;

		foreach ($this->foreignFields as $foreignField)
		{
			$filter = [
				$foreignField => [
					'$in' => $ids
				]
			];

			$update = ['$set' => [$foreignField => null]];

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

		return $aff;
	}
}
