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
 * Hookable trait
 *
 * @author Shay Anderson
 */
trait HookableTrait
{
	/**
	 * Hooks
	 *
	 * @var array
	 */
	private array $hooks = [];

	/**
	 * Invoke hooks by name
	 *
	 * @param string $name
	 * @param mixed ...$values
	 * @return void
	 */
	final protected function hook(string $name, mixed &...$values): void
	{
		if (isset($this->hooks[$name]))
		{
			foreach ($this->hooks[$name] as $cb)
			{
				$cb(...$values);
			}
		}
	}

	/**
	 * Hook setter
	 *
	 * @param array|string $name String like "name" or array like ["name", "name2", ...]
	 * @param callable $callback
	 * @return void
	 */
	final public function on(array|string $name, callable $callback): void
	{
		if (is_array($name))
		{
			foreach ($name as $v)
			{
				$this->on($v, $callback);
			}

			return;
		}

		$this->hooks[$name][] = $callback;
	}
}
