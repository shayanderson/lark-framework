<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

use App\App;
use Lark\Database;
use Lark\Debugger;
use Lark\Debugger\Info as DebuggerInfo;
use Lark\Debugger\Template as DebuggerTemplate;
use Lark\Env;
use Lark\Logger;
use Lark\Request;
use Lark\Response;
use Lark\Router;

/**
 * Lark helper functions
 */

/**
 * App instance getter
 *
 * @return \App\App
 */
function app(): App
{
	return App::getInstance();
}

/**
 * Database collection factory
 *
 * @param string $name
 * @return Database
 */
function db(string $name): Database
{
	return app()->db($name);
}

/**
 * Environment variables helper
 *
 * @param string $key
 * @param mixed $default (throws exception on invalid key if argument is not set)
 * @return mixed
 * @throws \Lark\Exception (on invalid key when $default argument is not set)
 */
function env(string $key, $default = null)
{
	$invalidKeyException = func_num_args() == 1;
	return Env::getInstance()->get($key, $default, $invalidKeyException);
}

/**
 * Return a formatted string
 *
 * @param string $format
 * @param mixed ...$values
 * @return string
 */
function f(string $format, ...$values): string
{
	if (is_array($values[0] ?? null)) // array keys
	{
		$values = $values[0];

		return preg_replace_callback('/{([\w]*?)}/', function ($m) use (&$values)
		{
			return $values[$m[1]] ?? null;
		}, $format);
	}

	// match '{abc_01}'
	return preg_replace_callback('/{[\w]*?}/', function ($m) use (&$values)
	{
		return array_shift($values);
	}, $format);
}

/**
 * Stop execution with response status code and optional message
 *
 * @param integer $responseStatusCode
 * @param string|null $message
 * @return void
 */
function halt(int $responseStatusCode, string $message = null): void
{
	res()->code($responseStatusCode);

	if ($message)
	{
		res()->json(['message' => $message]);
	}
	else
	{
		router()->exit();
	}
}

/**
 * Logger helper
 *
 * @param string $channel
 * @return \Lark\Logger
 */
function logger(string $channel = ''): Logger
{
	return new Logger($channel);
}

/**
 * Var formatted printer
 *
 * @param mixed ...$values
 * @return void
 */
function p(...$values): void
{
	DebuggerTemplate::info(
		(new DebuggerInfo(debug_backtrace(), ...$values)),
		true
	);
}

/**
 * Var printer
 *
 * @param mixed ...$values
 * @return void
 */
function pa(...$values): void
{
	if (!count($values))
	{
		$values = [null];
	}

	$prevStr = false;
	foreach ($values as $v)
	{
		if (is_scalar($v) || $v === null)
		{
			echo ($prevStr ? ' ' : null) . $v;
			$prevStr = true;
		}
		else
		{
			echo PHP_SAPI === 'cli'
				? ($prevStr ? PHP_EOL : null) . print_r($v, true)
				: '<pre>' . print_r($v, true) . '</pre>';
			$prevStr = false;
		}
	}

	if ($prevStr)
	{
		echo PHP_SAPI === 'cli' ? PHP_EOL : '<br />';
	}
}

/**
 * Request helper
 *
 * @return Request
 */
function req(): Request
{
	return app()->request();
}

/**
 * Response helper
 *
 * @return Response
 */
function res(): Response
{
	return app()->response();
}

/**
 * Router helper
 *
 * @return \Lark\Router
 */
function router(): Router
{
	return Router::getInstance();
}

/**
 * Debugger helper
 *
 * @param mixed ...$context
 * @return void
 */
function x(...$context): void
{
	Debugger::append(...$context);
	Debugger::dump();
}
