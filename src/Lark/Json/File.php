<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Json;

/**
 * JSON file
 *
 * @author Shay Anderson
 */
class File extends \Lark\File
{
	/**
	 * File contents getter
	 *
	 * @param boolean|null $associative
	 * @return mixed
	 * @throws \Lark\Exception on JSON decode error
	 */
	public function read(?bool $associative = null)
	{
		return Decoder::decode(
			parent::read(),
			$associative
		);
	}

	/**
	 * @inheritDoc
	 */
	public function write($data, $append = false, $lock = true): bool
	{
		return parent::write(
			json_encode($data),
			$append,
			$lock
		);
	}
}
