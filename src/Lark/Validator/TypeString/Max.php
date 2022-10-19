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
 * Max rule
 *
 * @author Shay Anderson
 */
class Max extends \Lark\Validator\Rule
{
	/**
	 * Max
	 *
	 * @var integer
	 */
	private int $max;

	/**
	 * Message
	 *
	 * @var string
	 */
	protected string $message = 'length must be a maximum of %s characters';

	/**
	 * Init
	 *
	 * @param integer $max
	 */
	public function __construct(int $max)
	{
		$this->max = $max;
	}

	/**
	 * Message getter
	 *
	 * @return string
	 */
	public function getMessage(): string
	{
		return sprintf($this->message, $this->max);
	}

	/**
	 * Validate
	 *
	 * @param string $value
	 * @return boolean
	 */
	public function validate($value): bool
	{
		return mb_strlen((string)$value) <= $this->max;
	}
}
