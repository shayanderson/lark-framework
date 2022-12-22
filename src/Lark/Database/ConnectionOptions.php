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

use MongoDB\Driver\ReadConcern;
use MongoDB\Driver\WriteConcern;

/**
 * Datbase connection options
 *
 * @author Shay Anderson
 */
class ConnectionOptions extends \Lark\Options
{
	/**
	 * Options keys
	 */
	const DB_ALLOW = 'db.allow';
	const DB_DENY = 'db.deny';
	const DEBUG_DUMP = 'debug.dump';
	const DEBUG_LOG = 'debug.log';
	const FIND_LIMIT = 'find.limit';
	const READ_CONCERN = 'read.concern';
	const WRITE_CONCERN = 'write.concern';

	/**
	 * Init
	 */
	public function __construct(string $connectionId)
	{
		parent::__construct([
			self::DB_ALLOW => [
				'array',
				'notNull',
				'default' => []
			],
			self::DB_DENY => [
				'array',
				'notNull',
				'default' => ['admin', 'config', 'local']
			],
			self::DEBUG_DUMP =>
			[
				'bool',
				'notNull',
				'default' => false
			],
			self::DEBUG_LOG => [
				'bool',
				'notNull',
				'default' => false
			],
			self::FIND_LIMIT => [
				'int',
				'notEmpty',
				'default' => 1_000
			],
			self::READ_CONCERN => [
				'object',
				'notNull',
				'default' => new ReadConcern
			],
			self::WRITE_CONCERN => [
				'object',
				'notNull',
				'default' => new WriteConcern(WriteConcern::MAJORITY)
			],
		], "Database options for connection ID \"{$connectionId}\"");
	}
}
