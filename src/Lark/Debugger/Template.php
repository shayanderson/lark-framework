<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Debugger;

use Lark\Debugger\Template\Cli;
use Lark\Debugger\Template\Html;

/**
 * Debugger info dump template
 *
 * @author Shay Anderson
 */
class Template extends AbstractTemplate
{
	/**
	 * @inheritDoc
	 */
	protected static function backtraceToString(
		array $backtrace,
		bool $isPrinter = false
	): string
	{
		return call_user_func_array([self::class(), 'backtraceToString'], func_get_args());
	}

	/**
	 * Class getter
	 *
	 * @return string
	 */
	private static function class(): string
	{
		return PHP_SAPI === 'cli' ? Cli::class : Html::class;
	}

	/**
	 * @inheritDoc
	 */
	public static function footer(string $class, int $count): void
	{
		call_user_func_array([self::class(), 'footer'], func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public static function group(array $group): void
	{
		call_user_func_array([self::class(), 'group'], func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public static function info(Info $info, bool $isPrinter = false): void
	{
		call_user_func_array([self::class(), 'info'], func_get_args());
	}
}
