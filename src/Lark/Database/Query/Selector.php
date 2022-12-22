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

use Lark\Database\Convert;

/**
 * Query selector
 *
 * @author Shay Anderson
 */
class Selector
{
	/**
	 * Field name
	 *
	 * @var string
	 */
	private string $field;

	/**
	 * Selector
	 *
	 * @var array
	 */
	private array $selector;

	/**
	 * Field is object ID flag
	 *
	 * @var boolean
	 */
	private bool $isObjectId;

	/**
	 * Init
	 *
	 * @param string $field
	 * @param array $selector
	 * @param boolean $isObjectId
	 */
	public function __construct(string $field, array $selector, bool $isObjectId)
	{
		$this->field = $field;
		$this->isObjectId = $isObjectId;
		$this->selector = [];

		// validate selector
		if ($this->field[0] === '$')
		{
			throw new DatabaseQueryException('Invalid selector for "' . $this->field . '"', [
				'selector' => $selector
			]);
		}

		foreach ($selector as $s => $v)
		{
			$this->add($s, $v);
		}
	}

	/**
	 * Add selector
	 *
	 * @param string $selector
	 * @param mixed $value
	 * @return void
	 */
	private function add(string $selector, mixed $value): void
	{
		// comparison
		if (in_array($selector, [
			'$eq',
			'$gt',
			'$gte',
			'$in',
			'$lt',
			'$lte',
			'$ne',
			'$nin'
		]))
		{
			$this->validateValue($value);
			$this->convertIdsToObjectIds($value);
			$this->selector[$selector] = $value;
		}
		else
		{
			throw new DatabaseQueryException('Invalid query selector', [
				'selector' => $selector
			]);
		}
	}

	/**
	 * Convert IDs to object IDs
	 *
	 * @param mixed $value
	 * @return void
	 */
	private function convertIdsToObjectIds(mixed &$value): void
	{
		if (!$this->isObjectId)
		{
			return;
		}

		if (is_string($value))
		{
			$value = Convert::idToObjectId($value);
		}
		else if (is_array($value))
		{
			foreach ($value as &$v)
			{
				$v = Convert::idToObjectId($v);
			}
		}
	}

	/**
	 * Selector getter
	 *
	 * @return array
	 */
	public function get(): array
	{
		return $this->selector;
	}

	/**
	 * Check if value is scalar or null
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	private static function isValueScalarOrNull(mixed $value): bool
	{
		return is_scalar($value) || $value === null;
	}

	/**
	 * Selector value must be scalar, or null, or array of scalar or null values
	 *
	 * @param mixed $value
	 * @return void
	 */
	private function validateValue(mixed $value): void
	{
		if (self::isValueScalarOrNull($value))
		{
			return;
		}

		if (is_array($value))
		{
			$isValidArr = true;

			// check all array values are scalar or null
			foreach ($value as $k => $v)
			{
				if (!self::isValueScalarOrNull($v))
				{
					$isValidArr = false;
					break;
				}
			}

			if ($isValidArr)
			{
				return;
			}
		}

		throw new DatabaseQueryException(
			'Query selector value for field "' . $this->field
				. '" must be scalar or null, or an array of scalar or null values'
		);
	}
}
