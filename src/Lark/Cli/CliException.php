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
 * CLI app exception
 *
 * @author Shay Anderson
 */
class CliException extends \Lark\Exception
{
	/**
	 * Status code
	 *
	 * @var int
	 */
	protected $code = 1;
}
