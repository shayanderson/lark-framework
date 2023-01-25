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

use Lark\Map\Path as MapPath;

/**
 * Query parameter
 *
 * @author Shay Anderson
 */
abstract class Parameter
{
	/**
	 * Field name
	 *
	 * @var string
	 */
	private string $field;

	/**
	 * Value
	 *
	 * @var mixed
	 */
	private mixed $value;

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
		$this->field = self::autoFieldName($field);
		$this->value = $value;
	}

	/**
	 * Auto field name for "id" => "_id"
	 *
	 * @param string $field
	 * @return string
	 */
	protected static function autoFieldName(string $field): string
	{
		return $field === 'id' ? '_id' : $field;
	}

	/**
	 * Parameter object factory
	 *
	 * @param array $modelSchema
	 * @param string $fieldName
	 * @param mixed $fieldValue
	 * @param integer $dpp
	 * @return Parameter
	 */
	public static function factory(
		array $modelSchema,
		string $fieldName,
		mixed $fieldValue,
		int $dpp
	): Parameter
	{
		$modelSchemaAssoc = self::modelSchemaToAssocArray($modelSchema);

		// special field like ($page: 1) or ($or: [...])
		if ($fieldName[0] === '$')
		{
			return new Option($modelSchemaAssoc, $fieldName, $fieldValue, $dpp);
		}
		// field value selector (field: value) or (field: [...])
		else
		{
			return new Condition($modelSchemaAssoc, $fieldName, $fieldValue, $dpp);
		}
	}

	/**
	 * Field name getter
	 *
	 * @return string
	 */
	public function getField(): string
	{
		return $this->field;
	}

	/**
	 * Field value getter
	 *
	 * @return mixed
	 */
	public function getValue(): mixed
	{
		return $this->value;
	}

	/**
	 * Convert schema array with rules to associative array to check if
	 * field exists in schema (check if allowed in query)
	 *
	 * @param array $modelSchema
	 * @return array
	 */
	private static function modelSchemaToAssocArray(array &$modelSchema): array
	{
		$schema = [];

		foreach ($modelSchema as $f => $rules)
		{
			if (!is_array($rules))
			{
				// convert like "string" to ["string"]
				$rules = [$rules];
			}

			// check rule type
			if (in_array($rules[0], ['array', 'arr', 'object', 'obj']))
			{
				$isNestedRule = false;

				foreach ($rules as $rule)
				{
					if (
						is_array($rule)
						&& (isset($rule['fields'])
							|| isset($rule['schema:array'])
							|| isset(
								$rule['schema:object']
							)
						)
					)
					{
						$isNestedRule = true;
						$isRuleTypeUsed = false;

						// nested fields + nested schemas
						foreach (['fields', 'schema:array', 'schema:object'] as $ruleType)
						{
							if (isset($rule[$ruleType]))
							{
								$schema[$f] = self::modelSchemaToAssocArray($rule[$ruleType]);
								$isRuleTypeUsed = true;
								break;
							}
						}

						if (!$isRuleTypeUsed)
						{
							throw new DatabaseQueryException(
								'Failed to detect valid nested rule in schema for query field "'
									. $f . '"'
							);
						}
					}
				}

				if (!$isNestedRule)
				{
					$schema[$f] = true;
				}
			}
			else
			{
				$schema[$f] = true;
			}
		}

		return $schema;
	}

	/**
	 * Field name setter
	 *
	 * @param string $field
	 * @return void
	 */
	protected function setField(string $field): void
	{
		$this->field = $field;
	}

	/**
	 * Field value setter
	 *
	 * @param mixed $value
	 * @return void
	 */
	protected function setValue(mixed $value): void
	{
		$this->value = $value;
	}

	/**
	 * Validate model schema field exists
	 *
	 * @param array $modelSchemaAssoc
	 * @param string $field
	 * @return void
	 */
	protected static function validateModelSchemaField(
		array &$modelSchemaAssoc,
		string $field
	): void
	{
		if ($field === '_id')
		{
			// always allow "_id" field
			return;
		}

		if (!MapPath::has($modelSchemaAssoc, $field))
		{
			throw new DatabaseQueryException(
				'Query field "' . $field . '" must exist in model schema'
			);
		}
	}
}
