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
	 * @param bool $requireId require doc ID field
	 * @param bool $partial allow partial document
	 * @return array|object
	 */
	final public function make($data, bool $requireId = false, bool $partial = false)
	{
		$v = new Validator($data, self::schemaGet());

		if ($requireId)
		{
			$v->id();
		}

		if ($partial)
		{
			$v->partial();
		}

		return $v->make();
	}

	/**
	 * Make entity array
	 *
	 * @param array $data
	 * @param bool $requireId require doc ID field
	 * @param bool $partial allow partial documents
	 * @return array
	 */
	final public function &makeArray(
		array $data,
		bool $requireId = false,
		bool $partial = false
	): array
	{
		foreach ($data as $k => $v)
		{
			$data[$k] = $this->make($v, $requireId, $partial);
		}

		return $data;
	}

	/**
	 * Schema getter
	 *
	 * @return Schema
	 */
	abstract public static function schema(): Schema;

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
