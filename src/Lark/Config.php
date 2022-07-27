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

use Lark\Map\Path as MapPath;

/**
 * Configuration settings
 *
 * @author Shay Anderson
 *
 * #todo change name
 */
class Config
{
	/**
	 * Allowed paths
	 *
	 * @var array
	 */
	private static array $allowed = [
		// DB connections
		'db.connection.*',
		// DB global options
		'db.options',
		// debug flag
		'debug', #todo rm
		// custom validation rules
		'validator.rule.*.*'
	];

	/**
	 * Map
	 *
	 * @var array
	 */
	private static array $map = [];

	/**
	 * Getter
	 *
	 * @param string $path
	 * @param mixed $default (throws exception on invalid path if argument is not set)
	 * @return mixed
	 * @throws \Lark\Exception when invalid path and $default is not set
	 */
	public static function get(string $path, $default = null)
	{
		if (!MapPath::has(self::$map, $path))
		{
			// use default value
			if (func_num_args() > 1)
			{
				return $default;
			}

			// no default value
			throw new Exception('Invalid path, path does not exist and no default value was set', [
				'path' => $path
			]);
		}

		return MapPath::get(self::$map, $path);
	}

	/**
	 * Setter
	 *
	 * @param string $path
	 * @param mixed $value
	 * @return void
	 */
	public static function set(string $path, $value): void
	{
		self::validatePath($path);
		MapPath::set(self::$map, $path, $value);
	}

	/**
	 * Map getter
	 *
	 * @return array
	 */
	public static function toArray(): array
	{
		return self::$map;
	}

	/**
	 * Check if path is allowed, if not throw exception
	 *
	 * @param string $path
	 * @return void
	 * @throws \Lark\Exception when path not allowed
	 */
	private static function validatePath(string $path): void
	{
		// check for static value, like 'key1.key2'
		if (in_array($path, self::$allowed))
		{
			return;
		}

		// check for dynamic value, like 'key1.key2.*'
		$p = '';
		foreach (explode('.', $path) as $k => $part)
		{
			if ($k < 2)
			{
				$p .= ($p ? '.' : '') . $part;
				continue;
			}
			$p .= '.*';
		}

		if (in_array($p, self::$allowed))
		{
			return;
		}

		throw new Exception('Invalid bind path, path is not allowed', [
			'path' => $path
		]);
	}
}
