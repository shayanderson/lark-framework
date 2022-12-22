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

use Lark\Database\Query\Condition;
use Lark\Database\Query\DatabaseQueryException;
use Lark\Database\Query\Option;
use Lark\Database\Query\Parameter;
use Lark\Model;

/**
 * Database query from array
 *
 * @author Shay Anderson
 */
class Query
{
	/**
	 * DB query
	 *
	 * @var array
	 */
	private array $dbQuery;

	/**
	 * DB query options
	 *
	 * @var array
	 */
	private array $dbQueryOptions;

	/**
	 * Documents per page
	 *
	 * @var integer
	 */
	private int $dpp;

	/**
	 * Is $or query flag
	 *
	 * @var boolean
	 */
	private bool $isOr = false;

	/**
	 * Option $limit
	 *
	 * @var integer
	 */
	private int $limit;

	/**
	 * Model
	 *
	 * @var Model
	 */
	private Model $model;

	/**
	 * Query
	 *
	 * @var array
	 */
	private array $query;

	/**
	 * Init
	 *
	 * @param Model $model
	 * @param array $query
	 */
	public function __construct(Model $model, array $query)
	{
		$this->model = $model;
		$this->query = $query;
		$this->dpp = $model->db()->connection()->getOptions()['find.limit'] ?? 1_000;

		// extract $limit value if set
		if (array_key_exists('$limit', $this->query))
		{
			Option::validateLimitValue($this->query['$limit'], $this->dpp);
			$this->limit = $this->query['$limit'];
		}

		// check if $or query
		if (array_key_exists('$or', $this->query))
		{
			if ($this->query['$or'] === true)
			{
				$this->isOr = true;
			}
			unset($this->query['$or']);
		}

		$this->build();
	}

	/**
	 * Build query
	 *
	 * @return void
	 */
	private function build(): void
	{
		if (isset($this->dbQuery))
		{
			return;
		}

		$this->dbQuery = [];
		$this->dbQueryOptions = [];
		$pendingOptions = [];

		foreach ($this->getQuery() as $fieldName => $fieldValue)
		{
			if (!is_string($fieldName))
			{
				throw new DatabaseQueryException('Query field name or selector must be a string');
			}

			$param = Parameter::factory(
				$this->model->schema()->toArray(),
				$fieldName,
				$fieldValue,
				isset($this->limit) ? $this->limit : $this->dpp
			);

			if ($param instanceof Condition)
			{
				if (array_key_exists($param->getField(), $this->dbQuery))
				{
					throw new DatabaseQueryException(
						'Invalid query condition for "' . $fieldName
							. '", condition can only exists once in query'
					);
				}

				$this->dbQuery[$param->getField()] = $param->getValue();
			}
			else if ($param instanceof Option)
			{
				if (array_key_exists($param->getField(), $this->dbQueryOptions))
				{
					throw new DatabaseQueryException(
						'Invalid query option for "' . $fieldName
							. '", option can only exists once in query'
					);
				}

				$this->dbQueryOptions[$param->getField()] = $param->getValue();

				// check if pending options exist, query options can generate more options
				// like $page generates options $sort and $skip
				if ($param->hasPending())
				{
					$pendingOptions = array_merge($pendingOptions, $param->getPending());
				}
			}
		}

		if ($pendingOptions)
		{
			// add pending options to query options
			foreach ($pendingOptions as $option)
			{
				/** @var Option $option */

				// only set if does not already exists, or if forced override
				if (!isset($this->dbQueryOptions[$option->getField()]) || $option->isOverride())
				{
					$this->dbQueryOptions[$option->getField()] = $option->getValue();
				}
			}
		}

		if ($this->isOr)
		{
			// change query to `[$or => [[...], ...]]`
			$or = [];
			foreach ($this->dbQuery as $k => $v)
			{
				$or[] = [$k => $v];
			}
			$this->dbQuery = ['$or' => $or];
		}
	}

	/**
	 * Count with query
	 *
	 * @return integer
	 */
	public function count(): int
	{
		return $this->model->db()->count(
			$this->getDbQuery()
		);
	}

	/**
	 * DB query getter
	 *
	 * @return array
	 */
	public function getDbQuery(): array
	{
		return $this->dbQuery;
	}

	/**
	 * DB query options
	 *
	 * @return array
	 */
	public function getDbQueryOptions(): array
	{
		return $this->dbQueryOptions;
	}

	/**
	 * Query getter
	 *
	 * @return array
	 */
	public function getQuery(): array
	{
		return $this->query;
	}

	/**
	 * Find with query
	 *
	 * @return array
	 */
	public function find(): array
	{
		return $this->model->db()->find(
			$this->getDbQuery(),
			$this->getDbQueryOptions()
		);
	}
}
