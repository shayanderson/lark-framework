<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark;

/**
 * Lark app
 *
 * @author Shay Anderson
 */
class App extends Factory\Singleton
{
	/**
	 * Exit application
	 *
	 * @return void
	 */
	final public function exit(): void
	{
		Router::getInstance()->exit();
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
	 * Session instance getter
	 *
	 * @return \Lark\Request\Session
	 */
	final public function session(): Request\Session
	{
		return Request\Session::getInstance();
	}

	/**
	 * Config setter
	 *
	 * @param string $path
	 * @param mixed $args
	 * @return void
	 */
	final public function use(string $path, $args): void
	{
		Binding::set($path, $args);
	}
}
