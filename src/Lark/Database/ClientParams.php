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

/**
 * Database client options
 *
 * @author Shay Anderson
 */
class ClientParams extends \Lark\Options
{
	/**
	 * Options keys
	 */
	const HOSTS = 'hosts';
	const OPTIONS = 'options';
	const PASSWORD = 'password';
	const REPLICASET = 'replicaSet';
	const USERNAME = 'username';

	/**
	 * Init
	 */
	public function __construct(string $connectionId)
	{
		parent::__construct([
			self::HOSTS => [
				'array',
				'notEmpty'
			],
			self::USERNAME => [
				'string',
				'notEmpty'
			],
			self::PASSWORD => [
				'string',
				'notNull'
			],
			self::REPLICASET => [
				'string',
				'notNull',
				'voidable'
			],
			self::OPTIONS => [
				'array',
				'voidable'
			]
		], "Database connection parameters for connection ID \"{$connectionId}\"");
	}
}
