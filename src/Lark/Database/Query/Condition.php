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
 * Abstract query condition
 *
 * @author Shay Anderson
 */
class Condition extends Parameter
{
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

		// field must exist in model schema
		self::validateModelSchemaField($modelSchemaAssoc, $this->getField());

		$isObjectId = false;

		// check to convert ID string to object ID
		if ($this->getField() === '_id')
		{
			$isObjectId = true;
		}

		if (is_scalar($this->getValue()) || $this->getValue() === null)
		{
			if ($isObjectId)
			{
				// auto convert ID string to object ID
				$this->setValue(
					Convert::idToObjectId($this->getValue())
				);
			}
		}
		else if (is_array($this->getValue()))
		{
			$this->setValue(
				(new Selector($this->getField(), $this->getValue(), $isObjectId))->get()
			);
		}
		else
		{
			throw new DatabaseQueryException(
				'Invalid query field value and/or selector for field "' . $this->getField() . '"'
			);
		}
	}
}
