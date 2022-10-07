<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Database;

use Lark\Binding;
use Lark\Database;
use MongoDB\Client;

/**
 * Database connection
 *
 * @author Shay Anderson
 */
class Connection
{
	/**
	 * MongoDB Client object
	 *
	 * @var Client
	 */
	private Client $client;

	/**
	 * Config
	 *
	 * @var array
	 */
	private static array $config = [];

	/**
	 * Connections
	 *
	 * @var Connection[]
	 */
	private static array $connections = [];

	/**
	 * Database objects
	 *
	 * @var Database[]
	 */
	private static array $databases = [];

	/**
	 * Default connection ID
	 *
	 * @var string
	 */
	private static string $defaultConnectionId = '';

	/**
	 * ID
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * Options
	 *
	 * @var array
	 */
	private array $options;

	/**
	 * Init
	 *
	 * @param string $id
	 * @param array $options
	 * @param Client $client
	 */
	public function __construct(string $id, array $options, Client $client)
	{
		$this->id = $id;
		$this->options = $options;
		$this->client = $client;
	}

	/**
	 * Connection client getter
	 *
	 * @return Client
	 */
	public function client(): Client
	{
		return $this->client;
	}

	/**
	 * Database collection factory
	 *
	 * @param string ...$name "connId$db$coll"
	 * 		or "App\Model\ClassName"
	 * 		or "database", "collection"
	 * 		or "connectionId", "database", "collection"
	 * @return Database
	 */
	public static function factory(string ...$name): Database
	{
		if (!count($name))
		{
			throw new DatabaseException('Invalid database connection string (empty)');
		}

		// "connId$db$coll" or "db$coll"
		if (count($name) > 1)
		{
			$name = implode('$', $name);
		}
		else
		{
			$name = $name[0];
		}

		$model = null;

		// class name
		if (strpos($name, '$') === false)
		{
			if (!defined("{$name}::DBS"))
			{
				throw new DatabaseException('Invalid database connection string for Model', [
					'class' => $name
				]);
			}

			$model = new $name;
			$name = constant("{$name}::DBS");
		}

		[$connId, $db, $coll] = self::parseDatabaseString($name);
		$type = $model ? 1 : 0;

		if (!isset(self::$databases[$type][$connId][$db][$coll]))
		{
			self::$databases[$type][$connId][$db][$coll] = new Database(
				self::factoryConnection($connId),
				$db,
				$coll,
				$model
			);
		}

		return self::$databases[$type][$connId][$db][$coll];
	}

	/**
	 * Connection factory
	 *
	 * @param string $id
	 * @return Connection
	 */
	private static function &factoryConnection(string $id): Connection
	{
		// create connection
		if (!isset(self::$connections[$id]))
		{
			self::initConfig();

			// get connection params from config
			if (!isset(self::$config['connection'][$id]))
			{
				throw new DatabaseException(
					'No database connection found for ID "' . $id . '"'
				);
			}

			// create/validate client params
			$clientParams = (new ClientParams($id))
				->set(self::$config['connection'][$id])
				->make();

			// extract hosts
			$hosts = $clientParams['hosts'];

			// set connection options
			$connectionOptions = new ConnectionOptions($id);
			$connectionOptions->set(self::$config['options'] ?? []); // global
			$connectionOptions->set($clientParams['options'] ?? []); // connection

			unset($clientParams['hosts'], $clientParams['options']);

			self::$connections[$id] = new Connection(
				$id,
				$connectionOptions->make(),
				new Client('mongodb://' . implode(',', $hosts), $clientParams)
			);
		}

		return self::$connections[$id];
	}

	/**
	 * Default connection ID getter
	 *
	 * @return string
	 */
	public static function getDefaultConnectionId(): string
	{
		self::initConfig();
		return self::$defaultConnectionId;
	}

	/**
	 * ID getter
	 *
	 * @return string
	 */
	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * Options getter
	 *
	 * @return array
	 */
	public function getOptions(): array
	{
		return $this->options;
	}

	/**
	 * Init config
	 *
	 * @return void
	 */
	private static function initConfig(): void
	{
		if (self::$config)
		{
			return;
		}

		self::$config = Binding::get('db', []);

		// check for db connections in config
		if (!isset(self::$config['connection']))
		{
			throw new DatabaseException('No database connections found');
		}

		// set default connection ID
		if (is_array(self::$config['connection']))
		{
			self::$defaultConnectionId = array_key_first(self::$config['connection']);
		}

		// check for default connection ID
		if (!self::$defaultConnectionId)
		{
			throw new DatabaseException('Invalid database default connection ID');
		}
	}

	/**
	 * Parse database string to connectionId, database and collection
	 *
	 * @param string $databaseString
	 * @return array<int, string> {0: connectionId, 1:database, 2:collection}
	 */
	public static function parseDatabaseString(string $databaseString): array
	{
		$name = explode('$', $databaseString);

		if (count($name) === 2) // default connection
		{
			array_unshift($name, self::getDefaultConnectionId());
		}

		if (count($name) !== 3)
		{
			throw new DatabaseException('Invalid database string', [
				'databaseString' => $databaseString,
				'name' => $name
			]);
		}

		return $name;
	}
}
