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
use Lark\Database\Connection;
use Lark\Debugger;
use Lark\Debugger\Info as DebuggerInfo;
use Lark\Debugger\Template as DebuggerTemplate;
use Lark\Env;
use Lark\Exception;
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
 * @return App
 */
function app(): App
{
	return App::getInstance();
}

/**
 * Database collection factory
 *
 * @param string ...$name "connId.db.coll"
 * 		or "App\Model\ClassName"
 * 		or "database", "collection"
 * 		or "connectionId", "database", "collection"
 * @return Database
 */
function db(string ...$name): Database
{
	return Connection::factory(...$name);
}

/**
 * MongoDB UTCDateTime object getter
 *
 * @param int|float|string|DateTimeInterface $milliseconds
 * @return \MongoDB\BSON\UTCDateTime
 */
function db_datetime($milliseconds = null)
{
	return $milliseconds === null
		? new MongoDB\BSON\UTCDateTime
		: new MongoDB\BSON\UTCDateTime($milliseconds);
}

/**
 * Debug helper
 *
 * @param string $message
 * @param mixed $context
 * @param string $channelGroup
 * @return void
 */
function debug(string $message, $context = null, string $channelGroup = null): void
{
	if (func_num_args() === 3)
	{
		Debugger::append($context)->name($message)->group($channelGroup);
	}
	else if (func_num_args() === 2)
	{
		Debugger::append($context)->name($message);
	}
	else
	{
		Debugger::append($message);
	}

	(new Logger(
		$channelGroup ? $channelGroup : ''
	))->debug($message, $context);
}

/**
 * Environment variables getter
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
 * @param mixed $responseStatusCode
 * @param string|null $message
 * @return void
 */
function halt(int $responseStatusCode, $message = null): void
{
	res()->code($responseStatusCode);

	if ($message)
	{
		if (is_array($message) || is_object($message))
		{
			res()->json($message);
		}
		else
		{
			res()->json(['message' => $message]);
		}
	}
	else
	{
		router()->exit();
	}
}

/**
 * Generate random key
 *
 * @param int $length (length returned in bytes, minimum 8)
 * @return string
 */
function keygen(int $length = 32): string
{
	if ($length < 8)
	{
		throw new Exception('Invalid length used in ' . __FUNCTION__ . ', minimum length is 8');
	}

	if (!function_exists('random_bytes'))
	{
		return bin2hex(openssl_random_pseudo_bytes($length));
	}

	return bin2hex(random_bytes($length));
}

/**
 * Logger helper
 *
 * @param string $channel
 * @return Logger
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
 * Request instance getter
 *
 * @return Request
 */
function req(): Request
{
	return Request::getInstance();
}

/**
 * Response instance getter
 *
 * @return Response
 */
function res(): Response
{
	return Response::getInstance();
}

/**
 * Router instance getter
 *
 * @return Router
 */
function router(): Router
{
	return Router::getInstance();
}

/**
 * Debugger instance getter
 *
 * @param mixed ...$context
 * @return void
 */
function x(...$context): void
{
	Debugger::append(...$context);
	Debugger::dump();
}
