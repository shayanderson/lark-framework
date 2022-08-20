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

/**
 * Model schema
 *
 * @author Shay Anderson
 */
class Schema
{
	/**
	 * Field value callbacks
	 *
	 * @var array
	 */
	private array $callbacks;

	/**
	 * Default values
	 *
	 * @var array
	 */
	private array $defaults = [];

	/**
	 * Filter fields
	 *
	 * @var array
	 */
	private array $filter = [];

	/**
	 * Indexes
	 *
	 * @var array
	 */
	private array $indexes = [];

	/**
	 * Name
	 *
	 * @var string|null
	 */
	private ?string $name;

	/**
	 * Schema
	 *
	 * @var array
	 */
	private array $schema;

	/**
	 * Original schema array
	 *
	 * @var array
	 */
	private array $schemaOrig;

	/**
	 * Init
	 *
	 * @param array $schema
	 */
	public function __construct(array $schema, string $name = null)
	{
		if (!$schema)
		{
			throw new Exception('Schema cannot be empty');
		}

		$this->schemaOrig = $schema;

		// detect special fields $[field]
		foreach ($schema as $field => $rules)
		{
			if ($field[0] === '$')
			{
				switch ($field)
				{
					case '$filter':
						// auto db field filter
						$this->filter = $schema[$field];
						break;

					case '$index':
						// single index
						$this->indexes[] = $schema[$field];
						break;

					case '$indexes':
						// multiple indexes
						foreach ($schema[$field] as $idx)
						{
							$this->indexes[] = $idx;
						}
						break;

					default:
						throw new Exception('Schema field names cannot start with "$"', [
							'field' => $field
						]);
						break;
				}

				unset($schema[$field]);
			}
		}

		$this->schema = $schema;
		$this->name = $name;

		// extract defaults
		$this->defaults($this->schema);
	}

	/**
	 * Apply field value callback
	 *
	 * @param string $field
	 * @param callable $callback
	 * @return self
	 */
	public function apply(string $field, callable $callback): self
	{
		#todo verify field (and nested field) exists in schema
		$this->callbacks[$field] = $callback;
		return $this;
	}

	/**
	 * Field default value setter
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return self
	 */
	public function default(string $field, $value): self
	{
		#todo verify field (and nested field) exists in schema
		$this->defaults[$field] = $value;
		return $this;
	}

	/**
	 * Default values setter
	 *
	 * @param array $schema
	 * @param string|null $parent
	 * @return void
	 */
	private function defaults(array $schema, string $parent = null): void
	{
		foreach ($schema as $k => $v)
		{
			if ($k === 'default')
			{
				$this->default($parent, $v);
			}
			else if (is_array($v)) // default values can be array
			{
				// key cannot be array index or "fields" for nested fields
				$this->defaults($v, $parent . (!is_int($k) && $k !== 'fields'
					? ($parent ? '.' : null) . $k : null));
			}
		}
	}

	/**
	 * Field value callback getter
	 *
	 * @param string $field
	 * @return callable
	 * @throws Exception If schema field value callback does not exist
	 */
	public function getCallback(string $field): callable
	{
		if (!$this->hasCallback($field))
		{
			throw new Exception('Schema callback not found for field "' . $field . '"');
		}

		return $this->callbacks[$field];
	}

	/**
	 * Default field value getter
	 *
	 * @param string $field
	 * @return void
	 */
	public function getDefault(string $field)
	{
		return $this->defaults[$field] ?? null;
	}

	/**
	 * Default fields values getter
	 *
	 * @return array
	 */
	public function getDefaults(): array
	{
		return $this->defaults;
	}

	/**
	 * Field filter getter
	 *
	 * @return array
	 */
	public function getFilter(): array
	{
		return $this->filter;
	}

	/**
	 * Indexes getter
	 *
	 * @return array
	 */
	public function getIndexes(): array
	{
		$indexes = [];

		foreach ($this->indexes as $index)
		{
			$idx = ['key' => []];

			foreach ($index as $k => $v)
			{
				if ($k[0] === '$') // option, like "$name"
				{
					$idx[substr($k, 1)] = $v;
					continue;
				}

				// keys
				$idx['key'][$k] = $v;
			}

			$indexes[] = $idx;
		}

		return $indexes;
	}

	/**
	 * Name getter
	 *
	 * @return string|null
	 */
	public function getName(): ?string
	{
		return $this->name;
	}

	/**
	 * Check if field value callback exists
	 *
	 * @param string $field
	 * @return boolean
	 */
	public function hasCallback(string $field): bool
	{
		return isset($this->callbacks[$field]);
	}

	/**
	 * Check if field fitler exists
	 *
	 * @return boolean
	 */
	public function hasFilter(): bool
	{
		return count($this->filter) > 0;
	}

	/**
	 * Name setter
	 *
	 * @param string|null $name
	 * @return self
	 */
	public function name(?string $name): self
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * Schema getter
	 *
	 * @param boolean $original
	 * @return array
	 */
	public function toArray(bool $original = false): array
	{
		return $original ? $this->schemaOrig : $this->schema;
	}
}
