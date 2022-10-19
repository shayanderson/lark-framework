<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Validator\TypeString;

/**
 * Type rule
 *
 * @author Shay Anderson
 */
class Type extends \Lark\Validator\Rule
{
	/**
	 * Message
	 *
	 * @var string
	 */
	protected string $message = 'must be a string or null';

	/**
	 * Validate
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function validate($value): bool
	{
		return $value === null || is_string($value);
	}
}
