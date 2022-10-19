<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Database;

use Lark\Database;

/**
 * Database exception
 *
 * @author Shay Anderson
 */
class DatabaseException extends \Lark\Exception
{
	/**
	 * Init
	 *
	 * @param string $message
	 * @param Database|array $context
	 * @param int $code
	 * @param \Throwable $previous
	 */
	public function __construct(
		string $message = '',
		$context = null,
		int $code = 0,
		\Throwable $previous = null
	)
	{
		// auto convert Database object to context
		if ($context && $context instanceof Database)
		{
			$context = [
				'connectionId' => $context->connection()->getId(),
				'database' => $context->getDatabaseName(),
				'collection' => $context->getCollectionName()
			];
		}

		parent::__construct($message, $context, $code, $previous);
	}
}
