<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Validator\TypeArray;

/**
 * Unique rule
 *
 * @author Shay Anderson
 */
class Unique extends \Lark\Validator\Rule
{
	/**
	 * Message
	 *
	 * @var string
	 */
	protected string $message = 'array values must be unique';

	/**
	 * Validate
	 *
	 * @param array $value
	 * @return boolean
	 */
	public function validate($value): bool
	{
		if (!is_array($value))
		{
			return true; // no values to compare
		}

		return $value === array_unique($value);
	}
}
