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
 * IPv6 rule
 *
 * @author Shay Anderson
 */
class Ipv6 extends \Lark\Validator\Rule
{
	/**
	 * Message
	 *
	 * @var string
	 */
	protected string $message = 'must be valid IPv6 address';

	/**
	 * Validate
	 *
	 * @param string $value
	 * @return boolean
	 */
	public function validate($value): bool
	{
		return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
	}
}
