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
	 * Require document ID flag
	 *
	 * @var boolean
	 */
	private bool $isDocIdRequired = false;

	/**
	 * Partial document flag
	 *
	 * @var boolean
	 */
	private bool $isPartialDoc = false;

	/**
	 * Validation messages
	 *
	 * @var array
	 */
	private array $messages = [];

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
	 */
	public function __construct($document, $schema)
	{
		$this->doc = $document;
		$this->docOrig = $document;

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
			self::$ruleBinding = Config::get('validator.rule', []);
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
	 * Doc ID required flag setter
	 *
	 * @return self
	 */
	public function id(): self
	{
		$this->isDocIdRequired = true;
		return $this;
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
	 * Partial document flag setters
	 *
	 * @return self
	 */
	public function partial(): self
	{
		$this->isPartialDoc = true;
		return $this;
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
	 * @return array
	 */
	private function validateData(array $data, array $schema, array $parentPath = []): array
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
						if ($this->isDocIdRequired)
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

				// missing fields allowed (partial)
				if ($this->isPartialDoc)
				{
					continue;
				}

				$data[$field] = $this->schema->getDefault(
					($parentPath ? implode('.', $parentPath) . '.' : null) . $field
				);

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

					if ($rule === 'fields') // nested schema
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
							array_merge($parentPath, [$field])
						);

						if ($isObject)
						{
							$data[$field] = (object)$data[$field];
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
						. ucfirst($rule);
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
						throw new ValidatorException('Invalid ' . $fieldType . ' rule "' . $rule . '"', [
							'field' => $field,
							'type' => $fieldType,
							'ruleClass' => $ruleClass
						]);
					}

					throw $ex;
				}
			}
		}

		return $data;
	}
}
