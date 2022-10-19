<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Validator\TypeGeneric;

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
	protected string $message = 'must be any type';

	/**
	 * Validate
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function validate($value): bool
	{
		return true; // any type allowed
	}
}
