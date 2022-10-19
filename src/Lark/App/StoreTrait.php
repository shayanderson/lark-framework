<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\App;

use Lark\Map;

/**
 * Key value store
 *
 * @author Shay Anderson
 */
trait StoreTrait
{
	/**
	 * Map
	 *
	 * @var \Lark\Map
	 */
	private static Map $map;

	/**
	 * Init
	 */
	protected static function __init()
	{
		if (!isset(self::$map))
		{
			self::$map = new Map;
		}
	}

	/**
	 * Clear
	 *
	 * @param string $key
	 * @return void
	 */
	public function clear(string $key): void
	{
		self::__init();
		self::$map->clear($key);
	}

	/**
	 * Getter
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get(string $key)
	{
		self::__init();
		return self::$map->get($key);
	}

	/**
	 * Check if key exists
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has(string $key): bool
	{
		self::__init();
		return self::$map->has($key);
	}

	/**
	 * Setter
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function set(string $key, $value): void
	{
		self::__init();
		self::$map->set($key, $value);
	}

	/**
	 * To array
	 *
	 * @return array
	 */
	public function storeToArray(): array
	{
		self::__init();
		return self::$map->toArray();
	}
}
