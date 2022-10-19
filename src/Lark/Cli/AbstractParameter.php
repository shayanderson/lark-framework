<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Cli;

/**
 * CLI abstract paramater
 *
 * @author Shay Anderson
 */
abstract class AbstractParameter
{
	/**
	 * Default value
	 *
	 * @var mixed
	 */
	protected $default;

	/**
	 * Description
	 *
	 * @var string
	 */
	protected string $description;

	/**
	 * Has default value flag
	 *
	 * @var boolean
	 */
	protected bool $hasDefault;

	/**
	 * Required flag
	 *
	 * @var boolean
	 */
	protected bool $isRequired;

	/**
	 * Name
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * Options
	 *
	 * @var array
	 */
	protected array $options;

	/**
	 * Default value getter
	 *
	 * @return mixed
	 */
	public function getDefault()
	{
		return $this->default;
	}

	/**
	 * Description getter
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		return $this->description;
	}

	/**
	 * Name getter
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Options getter
	 *
	 * @return array
	 */
	public function getOptions(): array
	{
		return $this->options;
	}

	/**
	 * Check if default value exists
	 *
	 * @return boolean
	 */
	public function hasDefault(): bool
	{
		return $this->hasDefault;
	}

	/**
	 * Check if required
	 *
	 * @return boolean
	 */
	public function isRequired(): bool
	{
		return $this->isRequired;
	}
}
