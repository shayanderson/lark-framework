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

use Lark\Debugger\Dumper;
use Lark\Debugger\Info;

/**
 * Debugger
 *
 * @author Shay Anderson
 */
class Debugger
{
	/**
	 * Info objects
	 *
	 * @var array
	 */
	private static array $info = [];

	/**
	 * Append
	 *
	 * @param mixed ...$context
	 * @return Info
	 */
	public static function &append(...$context): Info
	{
		$info = new Info(
			self::extractBacktrace(debug_backtrace()),
			...$context
		);
		self::$info[] = $info;
		return $info;
	}

	/**
	 * Dump
	 *
	 * @param bool $exit
	 * @return void
	 */
	public static function dump(bool $exit = true): void
	{
		if (PHP_SAPI !== 'cli' && Binding::get('debug.dump', false))
		{
			// add HTTP request context
			$req = req();
			array_unshift(self::$info, (new Info([], [
				'method' => $req->method(),
				'path' => $req->path(),
				'headers' => $req->headers(),
				'queryString' => $req->queryString(),
				'body' => strpos($req->contentType(), 'multipart/form-data') !== false
					? ($_POST ?? [])
					: $req->body(false),
			]))->name('Lark\Request')->group('$lark'));
		}

		Dumper::dump(array_reverse(self::$info));

		if ($exit)
		{
			exit;
		}
	}

	/**
	 * Extract backtrace
	 *
	 * @param array $backtrace
	 * @return array
	 */
	private static function extractBacktrace(array $backtrace): array
	{
		$debugger = null;
		$debug = null;
		$nextKey = null;

		foreach ($backtrace as $k => $a)
		{
			// self or Database
			if (
				($a['class'] ?? null) === self::class
				|| ($a['class'] ?? null) === Database::class
				|| ($a['class'] ?? null) === Database\Field::class
			)
			{
				$debugger = $a;
				continue;
			}
			// x() or debug()
			else if (($a['function'] ?? null) === 'x' || ($a['function'] ?? null) === 'debug')
			{
				$debug = $a;
				continue;
			}
			else if (!$nextKey)
			{
				$nextKey = $k;
				break;
			}
		}

		if ($debug)
		{
			// add next if next has caller class
			if ($nextKey && isset($backtrace[$nextKey]['class']))
			{
				return [$debug, $backtrace[$nextKey]];
			}

			return [$debug];
		}

		// next caller class
		if ($nextKey && isset($backtrace[$nextKey]['class']))
		{
			return [$backtrace[$nextKey]];
		}

		if ($debugger)
		{
			return [$debugger];
		}

		return [$backtrace[$nextKey]];
	}

	/**
	 * Debugger append info for internal calls
	 *
	 * @param string $message
	 * @param mixed $context
	 * @param bool $force When true will ignore Lark "debug.dump" and "debug.log" settings
	 * @param bool $dumpOnly
	 * @return void
	 */
	public static function internal(
		string $message,
		$context = null,
		bool $force = false,
		bool $dumpOnly = false
	): void
	{
		static $isDump, $isLog;

		if ($isDump === null || $isLog === null)
		{
			$isDump = Binding::get('debug.dump', false);
			$isLog = Binding::get('debug.log', false);
		}

		if ($isDump || $force)
		{
			Debugger::append($context)
				->name($message)
				->group('$lark');
		}

		if (($isLog || $force) && !$dumpOnly)
		{
			(new Logger('$lark'))->debug($message, $context);
		}
	}
}
