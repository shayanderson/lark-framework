<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Json;

use Lark\Exception;

/**
 * JSON decoder
 *
 * @author Shay Anderson
 */
class Decoder
{
	/**
	 * Decode JSON string
	 *
	 * @param string $json
	 * @param boolean|null $associative
	 * @param integer $depth
	 * @param integer $flags
	 * @return mixed Null if the string cannot be decoded or data is deeper than the nesting limit
	 * @throws Exception On JSON decode error
	 */
	public static function decode(
		string $json,
		?bool $associative = null,
		int $depth = 512,
		int $flags = 0
	)
	{
		return json_decode($json, $associative, $depth, $flags | JSON_THROW_ON_ERROR);
	}
}
