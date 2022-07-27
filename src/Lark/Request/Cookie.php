<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Request;

/**
 * Request cookie
 *
 * @author Shay Anderson
 */
class Cookie extends AbstractInput
{
	/**
	 * Input type
	 */
	const TYPE = INPUT_COOKIE;

	/**
	 * Input array getter
	 *
	 * @return array
	 */
	protected static function &getInputArray(): array
	{
		return $_COOKIE;
	}
}
