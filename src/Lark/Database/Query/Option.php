<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Database\Query;

/**
 * Query option
 *
 * @author Shay Anderson
 */
class Option extends Parameter
{
	/**
	 * Override query options flag
	 *
	 * @var boolean
	 */
	private bool $isOverride = false;

	/**
	 * Pending options used in query
	 *
	 * @var array[Option]
	 */
	private array $pending = [];

	/**
	 * Init
	 *
	 * @param array $modelSchemaAssoc
	 * @param string $field
	 * @param mixed $value
	 * @param integer $dpp
	 */
	public function __construct(array &$modelSchemaAssoc, string $field, mixed $value, int $dpp)
	{
		parent::__construct($modelSchemaAssoc, $field, $value, $dpp);

		switch ($this->getField())
		{
			case '$filter':
			case '$projection':
				// field projection
				$this->setField('projection');

				$first = null;

				$projection = &self::prepAndValidateFieldsAndValues(
					$modelSchemaAssoc,
					$this->getValue(),
					[0, 1],
					'Query option $filter/$projection values must be an integer and only 1 or 0',
					// callback to ensure only equal values are used
					function ($value) use (&$first)
					{
						if ($first === null)
						{
							$first = $value;
						}
						else if ($value !== $first)
						{
							throw new DatabaseQueryException(
								'Query option $filter/$projection values must all be either 1s or 0s'
							);
						}
					}
				);

				$this->setValue($projection);
				break;

			case '$limit':
				// limit
				$this->setField('limit');
				$limit = $this->getValue();
				self::validateLimitValue($limit, $dpp);
				break;

			case '$page':
				// pagination
				$page = $this->getValue();

				if (!is_int($page))
				{
					throw new DatabaseQueryException('Query option $page value must be an integer');
				}

				if ($page < 1)
				{
					throw new DatabaseQueryException(
						'Query option $page value must be greater than or equal to 1'
					);
				}

				$this->setValue($dpp); // currPage * docsPerPage

				// add $limit as pending, may already be set in query
				$this->pending[] = new self($modelSchemaAssoc, '$limit', $dpp, $dpp);

				// add $skip: (currPage - 1) * docsPerPage
				$pending = new self($modelSchemaAssoc, '$skip', ($page - 1) * $dpp, $dpp);
				// always override skip if set in query options
				$pending->setIsOverride(true);
				$this->pending[] = $pending;

				// add default $sort
				$this->pending[] = new self($modelSchemaAssoc, '$sort', ['id' => 1], $dpp);
				break;

			case '$skip':
				// skip
				$this->setField('skip');

				if (!is_int($this->getValue()))
				{
					throw new DatabaseQueryException('Query option $skip value must be an integer');
				}

				if ($this->getValue() < 0)
				{
					throw new DatabaseQueryException(
						'Query option $skip value must be greater than or equal to 0'
					);
				}
				break;

			case '$sort':
				// document sort order
				$this->setField('sort');

				$sort = &self::prepAndValidateFieldsAndValues(
					$modelSchemaAssoc,
					$this->getValue(),
					[-1, 1],
					'Query option $sorts values must be an integer and only 1 or -1'
				);

				$this->setValue($sort);
				break;

			default:
				throw new DatabaseQueryException(
					'Invalid query option "' . $this->getField() . '"'
				);
				break;
		}
	}

	/**
	 * Pending options getter
	 *
	 * @return array[Option]
	 */
	public function getPending(): array
	{
		return $this->pending;
	}

	/**
	 * Check if pending options exist
	 *
	 * @return boolean
	 */
	public function hasPending(): bool
	{
		return !empty($this->pending);
	}

	/**
	 * Check if override other options
	 *
	 * @return boolean
	 */
	public function isOverride(): bool
	{
		return $this->isOverride;
	}

	/**
	 * Prepare + validate fields + values
	 *
	 * @param array $modelSchemaAssoc
	 * @param array $values
	 * @param array $allowedValues
	 * @param string $exceptionMessage
	 * @param callable|null $loopValueCallback
	 * @return array
	 */
	private static function &prepAndValidateFieldsAndValues(
		array &$modelSchemaAssoc,
		array $values,
		array $allowedValues,
		string $exceptionMessage,
		callable $loopValueCallback = null,
	): array
	{
		$vals = [];

		foreach ($values as $k => $v)
		{
			$k = self::autoFieldName($k);
			self::validateModelSchemaField($modelSchemaAssoc, $k);

			if (!in_array($v, $allowedValues))
			{
				throw new DatabaseQueryException($exceptionMessage);
			}

			if ($loopValueCallback)
			{
				$loopValueCallback($v);
			}

			$vals[$k] = $v;
		}

		return $vals;
	}

	/**
	 * Override flag setter
	 *
	 * @param boolean $isOverride
	 * @return void
	 */
	public function setIsOverride(bool $isOverride): void
	{
		$this->isOverride = $isOverride;
	}

	/**
	 * Validate limit value
	 *
	 * @param mixed $limit
	 * @param integer $dpp
	 * @return void
	 */
	public static function validateLimitValue(mixed $limit, int $dpp): void
	{
		if (!is_int($limit))
		{
			throw new DatabaseQueryException(
				'Query option $limit value must be an integer'
			);
		}

		if ($limit < 1)
		{
			throw new DatabaseQueryException(
				'Query option $limit value must be greater than 0'
			);
		}

		if ($limit > $dpp)
		{
			throw new DatabaseQueryException(
				'Query option $limit must be less than or equal to ' . $dpp
			);
		}
	}
}
