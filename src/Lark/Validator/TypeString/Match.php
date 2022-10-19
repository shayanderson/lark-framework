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

use Lark\Validator\ValidatorException;

/**
 * Match rule
 *
 * @author Shay Anderson
 */
class Match extends \Lark\Validator\Rule
{
	/**
	 * Message
	 *
	 * @var string
	 */
	protected string $message = 'value must be a match';

	/**
	 * Patter
	 *
	 * @var string
	 */
	private string $pattern;

	/**
	 * Init
	 *
	 * @param string $pattern
	 */
	public function __construct(string $pattern)
	{
		$this->pattern = $pattern;
	}

	/**
	 * Validate
	 *
	 * @param string $value
	 * @return boolean
	 */
	public function validate($value): bool
	{
		$result = preg_match($this->pattern, (string)$value);

		if (preg_last_error() !== PREG_NO_ERROR)
		{
			throw new ValidatorException('Invalid match pattern', [
				'pattern' => $this->pattern
			]);
		}

		return $result === 1;
	}
}
