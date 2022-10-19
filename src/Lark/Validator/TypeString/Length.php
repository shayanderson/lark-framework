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
 * Length rule
 *
 * @author Shay Anderson
 */
class Length extends \Lark\Validator\Rule
{
	/**
	 * Length
	 *
	 * @var integer
	 */
	private int $length;

	/**
	 * Message
	 *
	 * @var string
	 */
	protected string $message = 'length must be %s characters';

	/**
	 * Init
	 *
	 * @param integer $length
	 */
	public function __construct(int $length)
	{
		$this->length = $length;
	}

	/**
	 * Message getter
	 *
	 * @return string
	 */
	public function getMessage(): string
	{
		return sprintf($this->message, $this->length);
	}

	/**
	 * Validate
	 *
	 * @param string $value
	 * @return boolean
	 */
	public function validate($value): bool
	{
		return mb_strlen((string)$value) === $this->length;
	}
}
