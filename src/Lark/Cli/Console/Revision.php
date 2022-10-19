<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Cli\Console;

use DirectoryIterator;
use Lark\App;
use Lark\Cli\Console;
use ReflectionFunction;
use ReflectionNamedType;

/**
 * Console revision
 *
 * @author Shay Anderson
 */
class Revision
{
	/**
	 * Collection
	 *
	 * @var string
	 */
	private string $collection;

	/**
	 * Connection ID
	 *
	 * @var string
	 */
	private string $connectionId;

	/**
	 * Database
	 *
	 * @var string
	 */
	private string $database;

	/**
	 * Revision model
	 *
	 * @var RevisionModel
	 */
	private RevisionModel $model;

	/**
	 * Path
	 *
	 * @var string
	 */
	private string $path;

	/**
	 * Revision ID
	 *
	 * @var string
	 */
	private string $revId;

	/**
	 * Init
	 *
	 * @param string $path
	 * @param string $revId
	 * @param string $connectionId
	 * @param string $database
	 * @param string $collection
	 */
	public function __construct(
		string $path,
		string $revId,
		string $connectionId,
		string $database,
		string $collection
	)
	{
		$this->path = $path;
		$this->revId = $revId;
		$this->connectionId = $connectionId;
		$this->database = $database;
		$this->collection = $collection;

		$this->model = new RevisionModel(
			$this->connectionId,
			$this->database,
			$this->collection,
			$this->revId
		);
	}

	/**
	 * Execute revision
	 *
	 * @param string $revId
	 * @return boolean|null
	 */
	private static function exec(string $revId): ?bool
	{
		$rev = RevisionFile::fromRevIdToObject($revId);

		if (!file_exists($rev->getPath()))
		{
			throw new ConsoleException(
				'Revision "' . $revId . '" file does not exist "' . $rev->getPath() . '"'
			);
		}

		$fn = require $rev->getPath();

		$rfn = new ReflectionFunction($fn);

		$params = $rfn->getParameters();
		$arg = null;

		if (isset($params[0]))
		{
			$type = $params[0]->getType();

			if ($type instanceof ReflectionNamedType)
			{
				$className = $type->getName();

				if (strpos($className, 'Lark\Database') === false)
				{
					// inject model
					$arg = new $className;
				}
			}
		}

		if ($arg === null)
		{
			// inject db
			$arg = App::getInstance()->db($rev->getDatabaseString());
		}

		return $fn($arg);
	}

	/**
	 * Collection getter
	 *
	 * @return string
	 */
	public function getCollection(): string
	{
		return $this->collection;
	}

	/**
	 * Connection ID getter
	 *
	 * @return string
	 */
	public function getConnectionId(): string
	{
		return $this->connectionId;
	}

	/**
	 * Database getter
	 *
	 * @return string
	 */
	public function getDatabase(): string
	{
		return $this->database;
	}

	/**
	 * Database string getter
	 *
	 * @return string
	 */
	public function getDatabaseString(): string
	{
		return $this->getConnectionId() . '$' . $this->getDatabase() . '$' . $this->getCollection();
	}

	/**
	 * Path getter
	 *
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * Revision ID getter
	 *
	 * @return string
	 */
	public function getRevId(): string
	{
		return $this->revId;
	}

	/**
	 * Import rev into database if doesn't exist
	 *
	 * @return void
	 */
	public function import(): void
	{
		// insert rev if doesn't exist
		if (!$this->model()->hasRev())
		{
			$this->model()->insertRev();
		}
	}

	/**
	 * Revision model getter
	 *
	 * @return RevisionModel
	 */
	public function model(): RevisionModel
	{
		return $this->model;
	}

	/**
	 * Run commits for all revisions
	 *
	 * @param Console $console
	 * @param string $directory
	 * @param string $indent
	 * @return void
	 */
	public static function run(Console $console, string $directory, string $indent = ''): void
	{
		$checks = [];

		$fn_echo = function (string $message, string $end = PHP_EOL) use (&$console, &$indent): void
		{
			$console->output()->echo($indent . $message, $end);
		};

		// ensure all revs in DB
		foreach (new DirectoryIterator($directory) as $o)
		{
			// '.' or '..' or dir
			if ($o->isDot() || $o->isDir())
			{
				continue;
			}

			$rev = RevisionFile::fromPathToObject($o->getPathname(), $o->getFilename());

			// import into _revs if does not exist
			$rev->import();

			$checks[$rev->getConnectionId()][$rev->getDatabase()] = $rev;
		}

		if (!$checks)
		{
			$fn_echo('No revisions.');
			return;
		}

		foreach ($checks as $conn => $dbs)
		{
			foreach ($dbs as $db => $rev)
			{
				/** @var Revision $rev */

				$fn_echo(
					'Checking pending commits for "' . $conn . '.' . $db . '"...'
				);

				$pendingRevs = $rev->model()->getPendingRevs();

				if (!$pendingRevs)
				{
					continue;
				}

				$fn_echo(
					'Executing pending commits for "' . $conn . '.' . $db . '"...'
				);

				foreach ($pendingRevs as $r)
				{
					$fn_echo(
						'  Executing commit "' . $r[RevisionModel::FIELD_REV_ID] . '"...',
						''
					);

					$retval = self::exec($r[RevisionModel::FIELD_REV_ID]);
					$status = null;

					if ($retval === true)
					{
						$status = RevisionModel::STATUS_COMMITTED;
						$console->output()->ok('OK');
					}
					else if ($retval === null)
					{
						$status = RevisionModel::STATUS_COMMITTED;
						$console->output()->dim('OK');
					}
					else if ($retval === false)
					{
						$status = RevisionModel::STATUS_FAILED;
						$console->output()->error('Failed');
					}

					if ($status === null)
					{
						throw new ConsoleException(
							'Invalid status for revision "' . $r[RevisionModel::FIELD_REV_ID] . '"'
						);
					}

					// update rev status in DB
					$rev->model()->updateRevStatus($r[RevisionModel::FIELD_REV_ID], $status);
				}
			}
		}

		$fn_echo('Done.');
	}
}
