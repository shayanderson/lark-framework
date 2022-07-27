<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Validator;

/**
 * Abstract rule
 *
 * @author Shay Anderson
 */
abstract class Rule
{
	/**
	 * Message
	 *
	 * @var string
	 */
	protected string $message = 'unknown error';

	public function __construct()
	{
	}

	/**
	 * Message getter
	 *
	 * @return string
	 */
	public function getMessage(): string
	{
		return $this->message;
	}

	/**
	 * Validate
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	abstract public function validate($value): bool;
}
