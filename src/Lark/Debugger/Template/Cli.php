<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Debugger\Template;

use Lark\Cli\Output;
use Lark\Debugger\AbstractTemplate;
use Lark\Debugger\Info;

/**
 * CLI debugger template
 *
 * @author Shay Anderson
 */
class Cli extends AbstractTemplate
{
	/**
	 * Separator/divider length
	 */
	const SEP_DIVIDER_LEN = 90;

	/**
	 * @inheritDoc
	 */
	protected static function backtraceToString(array $backtrace, bool $isPrinter = false): string
	{
		foreach ($backtrace as $k => $a)
		{
			if (!isset($a['file']))
			{
				continue;
			}

			$isClass = isset($a['class']);
			$isFn = isset($a['function']) && !$isClass;

			if ($k)
			{
				#todo sep
			}

			if ($isFn)
			{
				self::out()->colorLightGray->echo(
					$a['function'] . '()',
					''
				);
			}
			if ($isClass)
			{
				self::out()->colorLightGray->echo(
					$a['class'] . $a['type'] . $a['function'] . '()',
					''
				);
			}

			self::out()->styleDim->echo(
				'  ' . self::formatPath($a['file']) . ':' . $a['line']
			);

			if ($isPrinter)
			{
				// only print top
				break;
			}
		}

		return '';
	}

	/**
	 * Output or return divider
	 *
	 * @param boolean $return
	 * @return void
	 */
	private static function div(bool $return = false)
	{
		$div = str_repeat('~', self::SEP_DIVIDER_LEN);

		if ($return)
		{
			return $div . PHP_EOL;
		}

		self::out()->colorLightGray->echo($div);
	}

	/**
	 * @inheritDoc
	 */
	public static function footer(string $class, int $count): void
	{
		self::out()->styleDim->echo($class . ': Displaying ' . $count . ' objects.');
		self::out()->echo();
	}

	/**
	 * @inheritDoc
	 */
	public static function group(array $group): void
	{
		$divGroup = str_repeat('~', self::SEP_DIVIDER_LEN);

		$header = false;
		foreach ($group as $info)
		{
			/** @var Info $info */

			if (!$header)
			{
				self::out()->colorPurple->echo($divGroup);
				self::out()->colorLightPurple->echo(
					strtoupper($info->getGroup())
				);
				$header = true;
			}

			self::info($info);
		}

		self::out()->colorPurple->echo($divGroup);
	}

	/**
	 * @inheritDoc
	 */
	public static function info(Info $info, bool $isPrinter = false): void
	{
		self::div();

		if (!$isPrinter)
		{
			self::out()->colorLightGray->echo('#' . $info->getId(), '');

			if ($info->hasName())
			{
				self::out()->styleBold->echo(' ' . $info->getName());
			}
			else
			{
				self::out()->echo();
			}
		}

		foreach ($info->toArray() as $v)
		{
			self::sep();
			self::out()->colorLightCyan->echo(self::varToString($v));
			self::sep();
		}

		self::backtraceToString($info->getBacktrace(), $isPrinter);
		self::div();
	}

	/**
	 * Output object getter
	 *
	 * @return Output
	 */
	private static function out(): Output
	{
		return \Lark\Cli::getInstance()->output();
	}

	/**
	 * Output or return separator
	 *
	 * @param boolean $return
	 * @return void
	 */
	private static function sep(bool $return = false)
	{
		$sep = str_repeat('-', self::SEP_DIVIDER_LEN);

		if ($return)
		{
			return $sep . PHP_EOL;
		}

		self::out()->colorGray->echo($sep);
	}

	/**
	 * Variable to string
	 *
	 * @param mixed $var
	 * @return string
	 */
	private static function varToString($var): string
	{
		if (is_scalar($var) || $var === null)
		{
			if (is_string($var))
			{
				$var = '"' . $var . '"';
			}
			else if ($var === null)
			{
				$var = 'null';
			}
			else if (is_bool($var))
			{
				if ($var === true)
				{
					$var = 'true';
				}
				else if ($var === false)
				{
					$var = 'false';
				}
			}

			return (string)$var;
		}

		return trim(print_r($var, true));
	}
}
