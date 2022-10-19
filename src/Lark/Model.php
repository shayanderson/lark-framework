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

use Lark\Database\Connection;

/**
 * Abstract model
 *
 * @author Shay Anderson
 */
abstract class Model
{
	/**
	 * Database string
	 */
	const DBS = null;

	/**
	 * Schema file
	 */
	const SCHEMA = null;

	/**
	 * Database instance getter
	 *
	 * @return Database
	 */
	final public function db(): Database
	{
		if (!static::DBS)
		{
			throw new Exception('Invalid database string for Model (Model::DBS)', [
				'class' => static::class
			]);
		}

		return Connection::factory(static::class);
	}

	/**
	 * Make entity
	 *
	 * @param array|object $data
	 * @param string $mode
	 * @return array|object
	 */
	final public function make($data, string $mode = Validator::MODE_CREATE)
	{
		return (new Validator($data, self::schemaGet(), $mode))->make();
	}

	/**
	 * Make entity array
	 *
	 * @param array $data
	 * @param string $mode
	 * @return array
	 */
	final public function &makeArray(array $data, string $mode = Validator::MODE_CREATE): array
	{
		foreach ($data as $k => $v)
		{
			$data[$k] = $this->make($v, $mode);
		}

		return $data;
	}

	/**
	 * Schema getter
	 *
	 * @param callable|array Callable for Schema object access like `function(Schema &$schema){}`
	 * 		or Array for setting schema array
	 * @return Schema
	 */
	public static function &schema(): Schema
	{
		static $schemas = [];

		if (!isset($schemas[static::class]))
		{
			$arg = func_num_args() ? func_get_arg(0) : null;
			$schema = null;

			if ($arg && is_array($arg))
			{
				$schema = $arg;
			}

			if (!$schema && !static::SCHEMA)
			{
				throw new Exception('Invalid schema for Model (Model::SCHEMA)', [
					'class' => static::class
				]);
			}

			$schemas[static::class] = new Schema(
				$schema ?? require DIR_SCHEMAS . '/' . static::SCHEMA
			);

			// check for callback
			if ($arg && is_callable($arg))
			{
				$arg($schemas[static::class]);
			}
		}

		return $schemas[static::class];
	}

	/**
	 * Schema with name getter
	 *
	 * @return Schema
	 */
	private static function &schemaGet(): Schema
	{
		$schema = static::schema();

		// set model class as name
		$schema->name(
			// rm namespace if exists
			strpos(static::class, '\\') !== false
				? substr(strrchr(static::class, '\\'), 1)
				: static::class
		);

		return $schema;
	}
}
