<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Cli\Console;

use DateTime;
use DateTimeZone;
use Lark\Cli\Console;
use Lark\Database\Connection;

/**
 * Console revision file
 *
 * @author Shay Anderson
 */
class RevisionFile extends \Lark\File
{
	/**
	 * @inheritDoc
	 */
	const TYPE = 'revision';

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
	 * Model class name
	 *
	 * @var string|null
	 */
	private ?string $modelClass = null;

	/**
	 * Revision ID
	 *
	 * @var string
	 */
	private string $revId;

	/**
	 * Schema
	 *
	 * @var array|null
	 */
	private ?array $schema = null;

	/**
	 * Init
	 *
	 * @param string $name
	 * @param string $description
	 */
	public function __construct(string $name, string $description)
	{
		// match database string 'conn$db$coll'
		$isModelName = strpos($name, '$') === false;

		// look up DBS in model
		if ($isModelName)
		{
			$inputName = new InputName($name);
			$className = ModelFile::classNameFromInputName($inputName);

			if (!class_exists($className))
			{
				p($className::DBS); #todo rm
				throw new ConsoleException(
					'Failed to get database string from model class "' . $className
						. '", class not found'
				);
			}

			$dbString = constant("{$className}::DBS");

			if (!$dbString)
			{
				throw new ConsoleException(
					'Failed to get database string from "' . $className . '::DBS"'
				);
			}

			$this->modelClass = $className;
			$this->schema = $className::schema()->toArray(true);
		}
		else
		{
			$dbString = $name;
		}

		list(
			$this->connectionId,
			$this->database,
			$this->collection
		) = Connection::parseDatabaseString($dbString);

		$this->revId = self::createRevId(
			$this->connectionId,
			$this->database,
			$this->collection,
			$description
		);

		parent::__construct(
			self::fromRevIdToPath($this->revId)
		);
	}

	/**
	 * Create revision file
	 *
	 * @param Console $console
	 * @param boolean $createCollection
	 * @return void
	 */
	public function create(Console $console, bool $createCollection): void
	{
		if ($this->exists())
		{
			throw new ConsoleException('Revision file "' . $this->path() . '" already exists');
		}

		$model = $this->model();

		$id = $model->insertRev();

		if (!$id)
		{
			throw new ConsoleException(
				'Failed to create revision in database for "' . $this->revId . '"'
			);
		}

		if (!$this->write(
			Template::revision(
				$this->revId,
				$this->modelClass,
				$this->schema,
				$createCollection
			)
		))
		{
			throw new ConsoleException('Failed to create revision file "' . $this->path() . '"');
		}

		$console->output()->ok('Revision created "' . $this->revId . '"');
		$console->output()->dim('Revision file created "' . $this->path() . '"');
	}

	/**
	 * Create revision ID
	 *
	 * @param string $connectionId
	 * @param string $database
	 * @param string $collection
	 * @param string $description
	 * @return string
	 */
	private static function createRevId(
		string $connectionId,
		string $database,
		string $collection,
		string $description
	): string
	{
		$dt = new DateTime;
		$dt->setTimezone(new DateTimeZone('UTC'));
		$d = $dt->format('Ymd');
		$t = $dt->format('Hisv');

		return $d . $t . '$' . $connectionId . '$' . $database . '$' . $collection . '$'
			. self::formatDescription($description);
	}

	/**
	 * Format description
	 *
	 * @param string $description
	 * @return string
	 */
	private static function formatDescription(string $description): string
	{
		$description = trim($description);
		// replace all non alphanum
		$description = preg_replace('/[^a-zA-Z0-9]/', '_', $description);
		// replace multiple "_" with single
		$description = preg_replace('/[_]{2,}/', '_', $description);
		$description = trim($description, '_');
		$description = strtolower($description);

		$maxLen = 60;
		if (strlen($description) > $maxLen)
		{
			throw new ConsoleException('Invalid revision description, length cannot exceed ' . $maxLen, [
				'description' => $description
			]);
		}

		return $description;
	}

	/**
	 * Path to revision object getter
	 *
	 * @param string $path
	 * @param string $filename
	 * @return Revision
	 */
	public static function fromPathToObject(string $path, string $filename): Revision
	{
		$name = $filename;
		if (substr($name, -4) === '.php') // rm '.php' ext
		{
			$name = substr($name, 0, strlen($name) - 4);
		}

		list($connId, $db, $coll) = self::parseRev($name);

		return new Revision($path, $name, $connId, $db, $coll);
	}

	/**
	 * Revision ID to revision object getter
	 *
	 * @param string $revId
	 * @return Revision
	 */
	public static function fromRevIdToObject(string $revId): Revision
	{
		return self::fromPathToObject(
			Console::getDir('revision') . '/' . $revId . '.php',
			$revId . '.php'
		);
	}

	/**
	 * Revision ID to path getter
	 *
	 * @param string $revId
	 * @return string
	 */
	public static function fromRevIdToPath(string $revId): string
	{
		return Console::getDir('revision') . '/' . $revId . '.php';
	}

	/**
	 * Revision model getter
	 *
	 * @return RevisionModel
	 */
	private function model(): RevisionModel
	{
		return new RevisionModel(
			$this->connectionId,
			$this->database,
			$this->collection,
			$this->revId
		);
	}

	/**
	 * Parse revision name to connectionId, database and collection
	 *
	 * @param string $name
	 * @return array<int, string>{0: connectionId, 1: database, 2: collection}
	 */
	private static function parseRev(string $name): array
	{
		$parts = explode('$', $name);

		if (count($parts) !== 5)
		{
			throw new Exception('Failed to parse revision', ['rev' => $name]);
		}

		return array_slice($parts, 1, 3);
	}

	/**
	 * Remove a revision (file + database)
	 *
	 * @param string $revId
	 * @return boolean
	 */
	public static function remove(string $revId): bool
	{
		$file = new parent(
			self::fromRevIdToPath($revId)
		);

		$file->existsOrException();

		return $file->delete();
	}
}
