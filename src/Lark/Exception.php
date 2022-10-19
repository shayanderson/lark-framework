<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark;

/**
 * Lark exception
 *
 * @author Shay Anderson
 */
class Exception extends \Exception
{
	/**
	 * Status code
	 *
	 * @var int
	 */
	protected $code = 500;

	/**
	 * Context
	 *
	 * @var array|null
	 */
	protected ?array $context;

	/**
	 * Init
	 *
	 * @param string $message
	 * @param array $context
	 * @param int $code
	 * @param \Throwable $previous
	 */
	public function __construct(
		string $message = '',
		array $context = null,
		int $code = 0,
		\Throwable $previous = null
	)
	{
		parent::__construct($message, $code, $previous);
		$this->context = $context;

		if ($code)
		{
			$this->code = $code;
		}
	}

	/**
	 * Context getter
	 *
	 * @return array|null
	 */
	final public function getContext(): ?array
	{
		return $this->context;
	}

	/**
	 * Method getter
	 *
	 * @return string|null
	 */
	final public function getMethod(): ?string
	{
		if (($class = ($this->getTrace()[0]['class'] ?? null)))
		{
			return $class . ($this->getTrace()[0]['type'] ?? null)
				. ($this->getTrace()[0]['function'] ?? null) . '()';
		}
		else if (($func = ($this->getTrace()[0]['function'] ?? null)))
		{
			return $func . '()';
		}

		return null;
	}

	/**
	 * Handle an exception
	 *
	 * @param \Throwable $th
	 * @param callable $handler
	 * @return void
	 */
	public static function handle(\Throwable $th, callable $handler): void
	{
		$info['message'] = $th->getMessage();

		if ($th->getCode())
		{
			$info['code'] = $th->getCode();
		}
		else
		{
			$info['code'] = 500;
		}

		$info['type'] = get_class($th);

		if (method_exists($th, 'getMethod'))
		{
			$info['source'] = $th->getMethod();
		}

		if (method_exists($th, 'getContext') && $th->getContext())
		{
			$info['context'] = $th->getContext();
		}

		$handler($info);
	}
}
