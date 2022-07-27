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

use Lark\Debugger\AbstractTemplate;
use Lark\Debugger\Info;

/**
 * HTML debugger template
 *
 * @author Shay Anderson
 */
class Html extends AbstractTemplate
{
	/**
	 * Style code block blue
	 *
	 * @var string
	 */
	private static $styleCodeBlue = 'background-color:#00204d; padding:10px 14px; color:#fff;'
		. ' border-radius:4px;';

	/**
	 * Style code block gray
	 *
	 * @var string
	 */
	private static $styleCodeGray = 'background-color:#222; padding:10px 14px; color:#fff;'
		. ' border-radius:4px;';

	/**
	 * @inheritDoc
	 */
	protected static function backtraceToString(array $backtrace, bool $isPrinter = false): string
	{
		$s = '';
		$sep = count($backtrace) > 1 && !$isPrinter
			? '<hr style="margin-top:8px; margin-bottom:8px; height:1px; background-color:#ccc;'
			. ' border:none;" />'
			: null;

		$i = 0;
		foreach ($backtrace as $a)
		{
			if (!isset($a['file']))
			{
				continue;
			}

			$isClass = isset($a['class']);
			$isFn = isset($a['function']) && !$isClass;
			$color = $i ? 'color:#777' : null;

			$s .= f(
				'{sep}<div style="margin-top:5px;{color}">'
					. '<div style="float:left; width:50%; color:#003782">'
					. '<b><i>{fn}{classMethod}</i></b></div>'
					. '<div style="float:right; width:50%; text-align:right;">'
					. '<i style="color:#555; padding-right:4px;">{file}:{line}</i></div>'
					. '<div style="clear:both;"></div>',
				$s ? $sep : null,
				$color,
				// fn
				$isFn ? $a['function'] . '()' : null,
				// class/method
				$isClass ? $a['class'] . $a['type'] . $a['function'] . '()' : null,
				// file
				self::formatPath($a['file']),
				// line
				$a['line'] ?? null
			);

			if ($isClass && isset($a['object']))
			{
				$s .= f(
					'<div style="margin-top:6px; color:#777;"><i>OBJECT</i> <div style="{style}">'
						. '<pre style="margin:0">{object}</pre></div></div>',
					self::$styleCodeGray,
					print_r($a['object'], true)
				);
			}

			if ($isClass && isset($a['args']))
			{
				if (count($a['args']))
				{
					$s .= f(
						'<div style="margin-top:6px; color:#777;"><i>ARGS</i> <div style="{style}">'
							. '<pre style="margin:0">{args}</pre></div></div>',
						self::$styleCodeGray,
						print_r($a['args'], true)
					);
				}
				else
				{
					$s .= '<div style="margin-top:6px; color:#777;"><i>ARGS</i> <i>(none)</i></div>';
				}
			}

			$s .= '</div>';

			$i++;

			if ($isPrinter)
			{
				// only print top
				break;
			}
		}

		return $s;
	}

	/**
	 * @inheritDoc
	 */
	public static function footer(string $class, int $count): void
	{
		echo f(
			'<div style="font-family: monospace; color:#888">'
				. '{class}: Displaying {count} objects.</div>',
			$class,
			$count
		);
	}

	/**
	 * @inheritDoc
	 */
	public static function group(array $group): void
	{
		echo '<div style="border:2px solid #9edb02; border-bottom:6px solid #9edb02;'
			. ' background-color:#d5fc68; border-radius:5px; margin:0 0 10px 0;'
			. ' font-family: monospace;">';

		$header = false;
		foreach ($group as $info)
		{
			/** @var Info $info */

			if (!$header)
			{
				echo f(
					'<div style="{style}">{title}</div>',
					'font-size:1.2rem; margin-bottom:4px; padding-top:4px; padding-left:10px;'
						. ' color:#4b6600',
					strtoupper($info->getGroup())
				);
				$header = true;
			}

			self::info($info);
		}

		echo f(
			'<div style="{style}">{title}</div></div>',
			'text-align:right; font-size:0.9rem; color:#4b6600; margin:0 8px 2px 0;',
			strtoupper($info->getGroup())
		);
	}

	/**
	 * @inheritDoc
	 */
	public static function info(Info $info, bool $isPrinter = false): void
	{
		$body = '';
		foreach ($info->toArray() as $v)
		{
			$body .= self::varToString($v);
		}

		$style = 'border:2px solid #777; border-bottom: 6px solid #777;'
			. ' background-color:#efefef; margin:6px 6px 10px 6px;'
			. ' padding:8px; font-family: monospace; border-radius:5px;';

		$name = null;
		if (!$isPrinter)
		{
			$name = f(
				'<div style="font-size:1rem; margin-bottom:4px;">'
					. '<span style="color:#555">#{id}</span>'
					. ' <b>{name}</b></div>',
				$info->getId(),
				$info->hasName() ? $info->getName() : null
			);
		}

		$backtraceStr = self::backtraceToString($info->getBacktrace(), $isPrinter);
		echo <<<"DIV"
<div style="{$style}">
{$name}
<div style="">{$body}</div>{$backtraceStr}
</div>
DIV;
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
				$var = '<i>null</i>';
			}
			else if (is_bool($var))
			{
				if ($var === true)
				{
					$var = '<i>true</i>';
				}
				else if ($var === false)
				{
					$var = '<i>false</i>';
				}
			}

			return f('<div style="{} margin-bottom:6px;">{}</div>', self::$styleCodeBlue, $var);
		}

		return f(
			'<div style="{} margin-bottom:8px;"><pre style="margin:0;">{}</pre></div>',
			self::$styleCodeBlue,
			print_r($var, true)
		);
	}
}
