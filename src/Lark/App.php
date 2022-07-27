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

use Lark\Filter;

/**
 * Lark app
 *
 * @author Shay Anderson
 */
class App extends Factory\Singleton
{
	/**
	 * Database collection factory
	 *
	 * @param string $name
	 * @return Database
	 */
	final public function db(string $name): Database
	{
		return Database\Connection::factory($name);
	}

	/**
	 * Filter helper
	 *
	 * @return \Lark\Filter
	 */
	final public function filter(): Filter
	{
		return Filter::getInstance();
	}

	/**
	 * Request helper
	 *
	 * @return \Lark\Request
	 */
	final public function request(): Request
	{
		return Request::getInstance();
	}

	/**
	 * Run app
	 *
	 * @return void
	 */
	final public function run(): void
	{
		Router::getInstance()->dispatch();
	}

	/**
	 * Response helper
	 *
	 * @return \Lark\Response
	 */
	final public function response(): Response
	{
		return Response::getInstance();
	}

	/**
	 * Session helper
	 *
	 * @return \Lark\Request\Session
	 */
	final public function session(): Request\Session
	{
		return Request\Session::getInstance();
	}

	/**
	 * Config helper
	 *
	 * @param string $path
	 * @param mixed $args
	 * @return void
	 */
	final public function use(string $path, $args): void
	{
		Config::set($path, $args);
	}
}
