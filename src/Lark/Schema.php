<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark;

use Closure;
use Lark\Database\Constraint;
use Lark\Database\Constraint\RefDelete;
use Lark\Database\Constraint\RefFk;
use Lark\Map\Path as MapPath;

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
	private array $callbacks = [];

	/**
	 * Database model schema constraints
	 *
	 * @var Constraint[]
	 */
	private array $constraints = [];

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
	 * Updated ($update) fields
	 *
	 * @var array
	 */
	private array $updated = [];

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

		$imports = [];

		// detect special fields $[field]
		foreach ($schema as $field => $rules)
		{
			if ($field[0] === '$')
			{
				switch ($field)
				{
					case '$created':
						// set created timestamp/datetime field
						$fields = $schema[$field];
						if (!is_array($fields))
						{
							$fields = [$fields => 'timestamp'];
						}
						foreach ($fields as $f => $type)
						{
							$this->default($f, function () use (&$type)
							{
								return self::getTime($type);
							});
						}
						break;

					case '$filter':
						// auto db field filter
						$this->filter = $schema[$field];
						break;

					case '$import':
						// import file schema for field
						$imports = $schema[$field];
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

					case Constraint::TYPE_REF_DELETE:
						// db constraint ref delete
						foreach ($schema[$field] as $coll => $fields)
						{
							$this->constraints[$field][] = new RefDelete($coll, $fields);
						}
						break;

					case Constraint::TYPE_REF_FK:
						// db constraint ref fk
						foreach ($schema[$field] as $coll => $fields)
						{
							foreach ((array)$fields as $lf => $ff)
							{
								$this->constraints[$field][] = new RefFk($coll, $lf, $ff);
							}
						}
						break;

					case '$updated':
						// set updated timestamp/datetime field (static callback)
						$fields = $schema[$field];
						if (!is_array($fields))
						{
							$fields = [$fields => 'timestamp'];
						}
						foreach ($fields as $f => $type)
						{
							$this->updated[] = $f;
							$this->callbacks[1][$f] = function () use (&$type)
							{
								return self::getTime($type);
							};
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

		if ($imports)
		{
			foreach ($imports as $f => $file)
			{
				$this->import($f, $file);
			}
		}

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
		$this->callbacks[0][$field] = $callback;
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
	 * @param bool $isStatic
	 * @return callable
	 * @throws Exception If schema field value callback does not exist
	 */
	public function getCallback(string $field, bool $isStatic = false): callable
	{
		if (!$this->hasCallback($field, $isStatic))
		{
			throw new Exception('Schema callback not found for field "' . $field . '"');
		}

		return $this->callbacks[$isStatic ? 1 : 0][$field];
	}

	/**
	 * Constraints by type getter
	 *
	 * @param string $type
	 * @return Constraint[]
	 */
	public function &getConstraints(string $type): array
	{
		if ($this->hasConstraint($type))
		{
			return $this->constraints[$type];
		}

		return [];
	}

	/**
	 * Default field value getter
	 *
	 * @param string $field
	 * @return void
	 */
	public function getDefault(string $field)
	{
		if (!array_key_exists($field, $this->defaults))
		{
			return null;
		}

		if ($this->defaults[$field] instanceof Closure)
		{
			return $this->defaults[$field]();
		}

		return $this->defaults[$field];
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
	 * Time getter
	 *
	 * @param string $type
	 * @return mixed
	 */
	private static function getTime(string $type): mixed
	{
		switch ($type)
		{
			case 'datetime':
				return new \DateTime;
				break;

			case 'dbdatetime':
				return new \MongoDB\BSON\UTCDateTime;
				break;

			case 'timestamp':
				return time();
				break;
		}

		throw new Exception('Schema invalid time type "' . $type . '"');
	}

	/**
	 * Updated ($update) fields getter
	 *
	 * @return array
	 */
	public function getUpdatedFields(): array
	{
		return $this->updated;
	}

	/**
	 * Check if field value callback exists
	 *
	 * @param string $field
	 * @param bool $isStatic
	 * @return boolean
	 */
	public function hasCallback(string $field, bool $isStatic = false): bool
	{
		return isset($this->callbacks[$isStatic ? 1 : 0][$field]);
	}

	/**
	 * Check if constraint exist
	 *
	 * @param string $type
	 * @return boolean
	 */
	public function hasConstraint(string $type): bool
	{
		return isset($this->constraints[$type]);
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
	 * Import schema file for field schema
	 *
	 * @param string $field
	 * @param string $file File name in schemas directory like "myschema" or "dir/myschema"
	 * @return void
	 */
	public function import(string $field, string $file): void
	{
		$file = ltrim($file, DIRECTORY_SEPARATOR);

		if (substr($file, -4) !== '.php')
		{
			$file .= '.php';
		}

		$f = new File(DIR_SCHEMAS . DIRECTORY_SEPARATOR . $file);

		if (!$f->exists())
		{
			throw new Exception('Schema file does not exist for field schema import', [
				'field' => $field,
				'file' => $file,
				'path' => $f->path()
			]);
		}

		MapPath::set($this->schema, $field, require $f->path());
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
