<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Factory;

use Lark\Exception;

/**
 * Singleton factory
 *
 * @author Shay Anderson
 */
abstract class Singleton
{
	/**
	 * Instances
	 *
	 * @var array
	 */
	private static $instances = [];

	/**
	 * Protected
	 */
	private function __construct()
	{
		if (method_exists($this, '__init'))
		{
			$this->__init();
		}
	}

	/**
	 * Not allowed
	 */
	private function __clone()
	{
		throw new Exception('This method is not allowed in Singleton', [
			'method' => '__clone'
		]);
	}

	/**
	 * Not allowed
	 */
	public function __wakeup()
	{
		throw new Exception('This method is not allowed in Singleton', [
			'method' => '__wakeup'
		]);
	}

	/**
	 * Instance getter
	 *
	 * @return self
	 */
	public static function getInstance(): self
	{
		$class = static::class;

		if (!isset(self::$instances[$class]))
		{
			self::$instances[$class] = new static;
		}

		return self::$instances[$class];
	}
}
