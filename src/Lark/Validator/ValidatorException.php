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
 * Validator exception
 *
 * @author Shay Anderson
 */
class ValidatorException extends \Lark\Exception
{
	/**
	 * @inheritDoc
	 */
	protected $code = 422;
}
