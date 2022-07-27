<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Validator\TypeNumber;

/**
 * Not empty rule
 *
 * @author Shay Anderson
 */
class NotEmpty extends \Lark\Validator\Rule
{
	/**
	 * Message
	 *
	 * @var string
	 */
	protected string $message = 'must be a number greater than zero';

	/**
	 * Validate
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function validate($value): bool
	{
		if (empty($value))
		{
			return false;
		}

		// check value like "0.0"
		if ((int)$value == 0 && (float)$value == 0)
		{
			return false;
		}

		return (float)$value > 0;
	}
}
