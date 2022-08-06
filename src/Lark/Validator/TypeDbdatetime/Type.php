<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Validator\TypeDbdatetime;

use MongoDB\BSON\UTCDateTime;

/**
 * Type rule
 *
 * @author Shay Anderson
 */
class Type extends \Lark\Validator\Rule
{
	/**
	 * @inheritDoc
	 */
	protected string $message = 'must be a MongoDB datetime object or null';

	/**
	 * @inheritDoc
	 */
	public function validate($value): bool
	{
		return $value === null || $value instanceof UTCDateTime;
	}
}
