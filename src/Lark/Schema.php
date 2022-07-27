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
	 * Default values
	 *
	 * @var array
	 */
	private array $defaults = [];

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
						throw new Exception('Schema field names cannot start with "$"');
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
				$this->defaults[$parent] = $v;
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
	 * Default value getter
	 *
	 * @param string $path
	 * @return void
	 */
	public function getDefault(string $path)
	{
		return $this->defaults[$path] ?? null;
	}

	/**
	 * Default values getter
	 *
	 * @return array
	 */
	public function getDefaults(): array
	{
		return $this->defaults;
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
	 * Name setter
	 *
	 * @param string|null $name
	 * @return self
	 */
	public function setName(?string $name): self
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
