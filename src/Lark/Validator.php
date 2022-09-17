<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark;

use Lark\Validator\ValidatorException;

/**
 * Validator
 *
 * @author Shay Anderson
 */
class Validator
{
	/**
	 * Validator modes
	 */
	const MODE_CREATE = 'create';
	const MODE_REPLACE = 'replace';
	const MODE_REPLACE_ID = 'replace+id';
	const MODE_UPDATE = 'update';
	const MODE_UPDATE_ID = 'update+id';

	/**
	 * Document
	 *
	 * @var array|object
	 */
	private $doc;

	/**
	 * Original document
	 *
	 * @var array|object
	 */
	private $docOrig;

	/**
	 * Validation messages
	 *
	 * @var array
	 */
	private array $messages = [];

	/**
	 * Validator mode
	 *
	 * @var string
	 */
	private string $mode;

	/**
	 * Rule bindings
	 *
	 * @var array|null
	 */
	private static ?array $ruleBinding = null;

	/**
	 * Schema
	 *
	 * @var Schema
	 */
	private Schema $schema;

	/**
	 * Validation status
	 *
	 * @var boolean|null
	 */
	private ?bool $status = null;

	/**
	 * Init
	 *
	 * @param array|object $document
	 * @param array|Schema $schema
	 * @param string $mode
	 */
	public function __construct($document, $schema, string $mode = self::MODE_CREATE)
	{
		$this->doc = $document;
		$this->docOrig = $document;
		$this->mode = $mode;

		if ($schema instanceof Schema)
		{
			$this->schema = $schema;
		}
		else
		{
			$this->schema = new Schema($schema);
		}

		if (self::$ruleBinding === null) // init
		{
			#todo test
			self::$ruleBinding = Binding::get('validator.rule', []);
		}

		if (!in_array($this->mode, [
			self::MODE_CREATE,
			self::MODE_REPLACE,
			self::MODE_REPLACE_ID,
			self::MODE_UPDATE,
			self::MODE_UPDATE_ID
		]))
		{
			throw new ValidatorException('Invalid validator mode "' . $this->mode . '"');
		}
	}

	/**
	 * Push validation message
	 *
	 * @param string $field
	 * @param string $message
	 * @param array $parentPath
	 * @return void
	 */
	private function addMessage(string $field, string $message, $parentPath = []): void
	{
		if (count($parentPath))
		{
			$field = implode('.', array_merge($parentPath, [$field]));
		}

		$this->messages[$field] = $message;
	}

	/**
	 * Assert
	 *
	 * @param callable|null $callback
	 * @return void
	 */
	public function assert(callable $callback = null): void
	{
		if (!$this->validate())
		{
			$name = $this->schema->getName();

			foreach ($this->messages as $field => $message)
			{
				$throw = true;

				if ($callback)
				{
					$throw = !(bool)$callback($field, $message, $name);
				}

				if ($throw)
				{
					throw new ValidatorException(
						'Validation failed: "' . ($name ? $name . '.' : null) . $field . '" '
							. $message,
						[
							'name' => $name,
							'field' => $field,
							'doc' => $this->docOrig
						]
					);
				}
				break;
			}
		}
	}

	/**
	 * Field type getter
	 *
	 * @param array $rules
	 * @return string
	 */
	private function extractFieldType(array &$rules): string
	{
		foreach ($rules as $k => $rule)
		{
			switch ($rule)
			{
				case 'arr':
				case 'array':
					unset($rules[$k]);
					return 'array';
					break;

				case 'bool':
				case 'boolean':
					unset($rules[$k]);
					return 'boolean';
					break;

				case 'datetime':
					unset($rules[$k]);
					return 'datetime';
					break;

				case 'dbdatetime':
					unset($rules[$k]);
					return 'dbdatetime';
					break;

				case 'float':
					unset($rules[$k]);
					return 'float';
					break;

				case 'int':
				case 'integer':
					unset($rules[$k]);
					return 'integer';
					break;

				case 'num':
				case 'number':
					unset($rules[$k]);
					return 'number';
					break;

				case 'obj':
				case 'object':
					unset($rules[$k]);
					return 'object';
					break;

				case 'str':
				case 'string':
					unset($rules[$k]);
					return 'string';
					break;

				case 'timestamp':
					unset($rules[$k]);
					return 'timestamp';
					break;
			}
		}

		return 'generic';
	}

	/**
	 * Check if mode
	 *
	 * @param string ...$mode
	 * @return boolean
	 */
	private function isMode(string ...$mode): bool
	{
		return in_array($this->mode, $mode);
	}

	/**
	 * Make entity
	 *
	 * @return array|object
	 */
	public function make()
	{
		$this->assert();
		return $this->doc;
	}

	/**
	 * Validate
	 *
	 * @return boolean
	 */
	public function validate(): bool
	{
		if ($this->status !== null)
		{
			return $this->status;
		}

		$isObject = is_object($this->doc);

		$this->doc = $this->validateData(
			$isObject ? (array)$this->doc : $this->doc,
			$this->schema->toArray()
		);

		if ($isObject)
		{
			// convert back to object
			$this->doc = (object)$this->doc;
		}

		$this->status = count($this->messages) === 0;

		return $this->status;
	}

	/**
	 * Validate data (recursive)
	 *
	 * @param array $data
	 * @param array $schema
	 * @param array $parentPath
	 * @param bool $isNestedSchema
	 * @return array
	 */
	private function validateData(
		array $data,
		array $schema,
		array $parentPath = [],
		bool $isNestedSchema = false
	): array
	{
		// add missing data fields from schema
		foreach ($schema as $field => $rules)
		{
			if (!array_key_exists($field, $data))
			{
				if (is_array($rules))
				{
					if (in_array('id', $rules))
					{
						// check if id field required
						if ($this->isMode(self::MODE_REPLACE_ID, self::MODE_UPDATE_ID))
						{
							$data[$field] = null; // auto set
						}

						continue; // id field is voidable
					}

					if (in_array('voidable', $rules))
					{
						continue; // voidable field allowed
					}
				}

				// check for schema field value static callback
				$fieldName = implode('.',  array_merge($parentPath, [$field]));
				if ($this->schema->hasCallback($fieldName, true))
				{
					$data[$field] = $this->schema->getCallback($fieldName, true)();
					// value set by static callback, continue
					continue;
				}

				// missing fields allowed (partial), partial docs not allowed for nested schemas
				if ($this->isMode(self::MODE_UPDATE, self::MODE_UPDATE_ID) && !$isNestedSchema)
				{
					continue;
				}

				// add default values only for create
				if ($this->isMode(self::MODE_CREATE))
				{
					$data[$field] = $this->schema->getDefault(
						($parentPath ? implode('.', $parentPath) . '.' : null) . $field
					);
				}
				else
				{
					$data[$field] = null;
				}

				// auto convert to object for empty value
				if (is_array($rules) && (in_array('object', $rules) || in_array('obj', $rules)))
				{
					$data[$field] = (object)$data[$field];
				}
			}
		}

		foreach ($data as $field => $value)
		{
			if (!array_key_exists($field, $schema)) // field exist in data but not in schema
			{
				$this->addMessage((string)$field, 'field does not exist in schema', $parentPath);
				continue;
			}

			// check for schema field value callback
			$fieldName = implode('.',  array_merge($parentPath, [$field]));
			if ($this->schema->hasCallback($fieldName))
			{
				$data[$field] = $value = $this->schema->getCallback($fieldName)($value);
			}
			// check for schema field value static callback
			if ($this->schema->hasCallback($fieldName, true))
			{
				$data[$field] = $this->schema->getCallback($fieldName, true)();
			}

			$rules = $schema[$field];

			if ($rules === null) // optional field, no rules
			{
				continue;
			}

			if (!is_array($rules)) // convert single rule into array
			{
				$rules = [$rules];
			}

			// extract type (+ remove type from rules)
			$fieldType = $this->extractFieldType($rules);

			// always validate type first
			array_unshift($rules, 'type');

			foreach ($rules as $rule)
			{
				if ($rule === 'voidable') // ignore
				{
					continue;
				}

				$ruleParams = [];

				// [rule => param] or [rule => [params]]
				if (is_array($rule))
				{
					$ruleTmp = array_key_first($rule);

					if ($ruleTmp === 'default')
					{
						// default value, used only by schema
						continue;
					}

					$ruleParams = $rule[$ruleTmp];
					$rule = $ruleTmp;

					if (!is_array($ruleParams))
					{
						$ruleParams = [$ruleParams];
					}

					if ($rule === 'fields') // nested fields
					{
						$isObject = false;
						if (is_object($value) || $fieldType === 'object')
						{
							$isObject = true;
							$value = (array)$value;
						}

						if (!is_array($value)) // missing value
						{
							$value = [];
						}

						$data[$field] = $this->validateData(
							$value,
							$ruleParams,
							array_merge($parentPath, [$field]),
							$isNestedSchema
						);

						if ($isObject)
						{
							$data[$field] = (object)$data[$field];
						}

						continue;
					}

					if ($rule == 'schema:array' || $rule == 'schema:object') // nested schema
					{
						if ($fieldType != 'array')
						{
							throw new ValidatorException(
								'Rule "' . $rule . '" can only be used with field type "array"'
							);
						}

						$isArray = $rule == 'schema:array';
						$data[$field] = [];
						$i = 0;
						foreach (($value ?? []) as $k => $v)
						{
							if ($k !== $i)
							{
								throw new ValidatorException(
									'Rule "' . $rule . '" can only be used with an array of '
										. ($isArray ? 'arrays' : 'objects')
								);
							}

							if ($isArray && !is_array($v))
							{
								$this->addMessage(
									$field . '.' . $k,
									'must be an array',
									array_merge($parentPath)
								);
								return [];
							}
							else if (!$isArray)
							{
								if (!is_object($v))
								{
									$this->addMessage(
										$field . '.' . $k,
										'must be an object',
										array_merge($parentPath)
									);
									return [];
								}

								$v = (array)$v;
							}

							$data[$field][$i] = $this->validateData(
								$v,
								$ruleParams,
								array_merge($parentPath, [$field, $k]),
								true
							);

							if (!$isArray)
							{
								$data[$field][$i] = (object)$data[$field][$i];
							}

							$i++;
						}

						continue;
					}
				}

				// check for rule binding
				if (isset(self::$ruleBinding[$fieldType][$rule]))
				{
					$ruleClass = self::$ruleBinding[$fieldType][$rule];
				}
				else
				{
					$ruleClass = '\\' . self::class . '\\Type' . ucfirst($fieldType) . '\\'
						. ucfirst((string)$rule);
				}

				// invoke dynamic rule
				try
				{
					$ruleObj = (new \ReflectionClass($ruleClass))->newInstanceArgs($ruleParams);

					if (!$ruleObj instanceof \Lark\Validator\Rule)
					{
						throw new ValidatorException(
							'Rule class must be subclass of Lark\\Validator\\Rule',
							[
								'class' => $ruleClass
							]
						);
					}

					if (!$ruleObj->validate($value))
					{
						// when not required verify rules
						if (
							!in_array('notNull', $rules)
							&& !in_array('notEmpty', $rules)
							&& !in_array('id', $rules)
						)
						{
							// allow null value for optional field
							if ($value === null)
							{
								continue;
							}
						}

						$this->addMessage($field, $ruleObj->getMessage(), $parentPath);

						return []; // failed, do not continue
					}
				}
				catch (\ReflectionException $ex)
				{
					if (strpos($ex->getMessage(), 'does not exist') !== false)
					{
						throw new ValidatorException(
							'Invalid ' . $fieldType . ' rule "' . $rule . '"',
							[
								'field' => $field,
								'type' => $fieldType,
								'ruleClass' => $ruleClass
							]
						);
					}

					throw $ex;
				}
			}
		}

		return $data;
	}
}
