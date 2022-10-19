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
 * Hash rule
 *
 * @author Shay Anderson
 */
class Hash extends \Lark\Validator\Rule
{
	/**
	 * Known hash
	 *
	 * @var string
	 */
	private string $knownHash;

	/**
	 * Message
	 *
	 * @var string
	 */
	protected string $message = 'hashes must be equal';

	/**
	 * Init
	 *
	 * @param string $knownHash
	 */
	public function __construct(string $knownHash)
	{
		$this->knownHash = $knownHash;
	}

	/**
	 * Validate
	 *
	 * @param string $value
	 * @return boolean
	 */
	public function validate($value): bool
	{
		return hash_equals($this->knownHash, $value);
	}
}
