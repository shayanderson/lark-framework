<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Debugger;

/**
 * Abstract debugger template
 *
 * @author Shay Anderson
 */
abstract class AbstractTemplate
{
	/**
	 * Backtrace to string
	 *
	 * @param array $backtrace
	 * @param boolean $isPrinter
	 * @return string
	 */
	abstract protected static function backtraceToString(
		array $backtrace,
		bool $isPrinter = false
	): string;

	/**
	 * Output footer
	 *
	 * @param string $class
	 * @param integer $count
	 * @return void
	 */
	abstract public static function footer(string $class, int $count): void;

	/**
	 * Format path
	 *
	 * @param string $path
	 * @return string
	 */
	protected static function formatPath(string $path): string
	{
		if (DIR_ROOT === substr($path, 0, strlen(DIR_ROOT)))
		{
			// strip root path
			$path = substr($path, strlen(DIR_ROOT) + 1);
		}

		return $path;
	}

	/**
	 * Set group
	 *
	 * @param array $group
	 * @return void
	 */
	abstract public static function group(array $group): void;

	/**
	 * Add info
	 *
	 * @param Info $info
	 * @param boolean $isPrinter
	 * @return void
	 */
	abstract public static function info(Info $info, bool $isPrinter = false): void;
}
