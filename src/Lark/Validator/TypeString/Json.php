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
 * JSON rule
 *
 * @author Shay Anderson
 */
class Json extends \Lark\Validator\Rule
{
	/**
	 * Message
	 *
	 * @var string
	 */
	protected string $message = 'must be a valid JSON';

	/**
	 * Validate
	 *
	 * @param string $value
	 * @return boolean
	 */
	public function validate($value): bool
	{
		if (!empty($value))
		{
			json_decode($value);
			return json_last_error() === JSON_ERROR_NONE;
		}

		return false;
	}
}
