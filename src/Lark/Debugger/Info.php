<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Debugger;

use Lark\Debugger;

/**
 * Debugger info
 *
 * @author Shay Anderson
 */
class Info
{
	/**
	 * Backtrace
	 *
	 * @var array
	 */
	private array $backtrace;

	/**
	 * Context
	 *
	 * @var array
	 */
	private array $context;

	/**
	 * Group name
	 *
	 * @var string
	 */
	private string $group;

	/**
	 * ID
	 *
	 * @var integer
	 */
	private int $id = 0;

	/**
	 * Name
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * Init
	 *
	 * @param array $backtrace
	 * @param mixed ...$context
	 */
	public function __construct(array $backtrace, ...$context)
	{
		$this->backtrace = $backtrace;
		$this->context = $context;
	}

	/**
	 * Append context
	 *
	 * @param mixed ...$context
	 * @return self
	 */
	public function context(...$context): self
	{
		$this->context = array_merge($this->context, $context);
		return $this;
	}

	/**
	 * Check if group exists
	 *
	 * @return boolean
	 */
	public function hasGroup(): bool
	{
		return isset($this->group);
	}

	/**
	 * Check if name exists
	 *
	 * @return boolean
	 */
	public function hasName(): bool
	{
		return isset($this->name);
	}

	/**
	 * Backtrace getter
	 *
	 * @return array
	 */
	public function getBacktrace(): array
	{
		return $this->backtrace;
	}

	/**
	 * Group name getter
	 *
	 * @return string|null
	 */
	public function getGroup(): ?string
	{
		if ($this->hasGroup())
		{
			return $this->group;
		}
	}

	/**
	 * ID getter
	 *
	 * @return integer
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * Name getter
	 *
	 * @return string|null
	 */
	public function getName(): ?string
	{
		if ($this->hasName())
		{
			return $this->name;
		}
	}

	/**
	 * Group name setter
	 *
	 * @param string $name
	 * @return self
	 */
	public function group(string $name): self
	{
		$this->group = $name;
		return $this;
	}

	/**
	 * ID setter
	 *
	 * @param integer $id
	 * @return void
	 */
	public function id(int $id): void
	{
		$this->id = $id;
	}

	/**
	 * Name setter
	 *
	 * @param string $name
	 * @return self
	 */
	public function name(string $name): self
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * Context getter
	 *
	 * @return array
	 */
	public function &toArray(): array
	{
		return $this->context;
	}
}
