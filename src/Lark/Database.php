<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark;

use Lark\Database\Connection;
use Lark\Database\ConnectionOptions;
use Lark\Database\Constraint;
use Lark\Database\Convert;
use Lark\Database\DatabaseException;
use Lark\Database\Field;
use Lark\Logger;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\Driver\Exception\CommandException;
use MongoDB\Driver\Exception\ConnectionTimeoutException;
use MongoDB\Driver\Command;
use MongoDB\Driver\Cursor;
use MongoDB\Operation\FindOneAndReplace;
use MongoDB\Operation\FindOneAndUpdate;

/**
 * Database
 *
 * @author Shay Anderson
 */
class Database
{
	/**
	 * Collection name
	 *
	 * @var string
	 */
	private string $collection = '';

	/**
	 * Collection object
	 *
	 * @var Connection
	 */
	private Connection $connection;

	/**
	 * Database name
	 *
	 * @var string
	 */
	private string $database = '';

	/**
	 * Model
	 *
	 * @var Model|null
	 */
	private ?Model $model;

	/**
	 * Init
	 *
	 * @param Connection $connection
	 * @param string $database
	 * @param string $collection
	 * @param Model|null $model
	 */
	public function __construct(
		Connection &$connection,
		string $database,
		string $collection,
		?Model $model
	)
	{
		$this->connection = &$connection;
		$this->setDatabase($database);
		$this->setCollection($collection);
		$this->model = $model;
	}

	/**
	 * Bulk write
	 *
	 * @param string $operation
	 * @param array $documents
	 * @param array $options
	 * @return array{0: int, 1: string[]} [affectd count, ids]
	 */
	private function bulkWrite(string $operation, array $documents, array $options): array
	{
		if (!$documents)
		{
			return $this->debug([0, []], __METHOD__ . ' ($documents is empty, nothing to do)');
		}

		$this->optionsWriteConcern($options);
		$timer = new Timer;
		Convert::inputIdToObjectIdArray($documents);
		$ops = [];
		$ids = [];

		$writeOptions = ['ordered' => true];
		if (array_key_exists('writeOptions', $options))
		{
			$writeOptions = $options['writeOptions'];
			unset($options['writeOptions']);
		}

		foreach ($documents as $doc)
		{
			if (is_object($doc))
			{
				$doc = (array)$doc;
			}

			if (!isset($doc['_id']))
			{
				throw new DatabaseException(
					'Bulk write method requires ID for all documents',
					$this
				);
			}

			$id = $doc['_id'];
			$ids[] = Convert::objectIdToString($id);
			unset($doc['_id']);

			$ops[] = [
				$operation => [
					['_id' => $id], // filter
					($operation === 'updateOne'
						? ['$set' => $doc] // update
						: $doc // other
					),
					$options
				]
			];
		}

		$count = 0;

		if (
			($res = $this->collection()->bulkWrite($ops, $writeOptions))
		)
		{
			$count += $res->getModifiedCount();
			$count += $res->getInsertedCount();
			$count += $res->getUpsertedCount();
		}

		return $this->debug([$count, $ids], __METHOD__, [
			'operation' => $operation,
			'operations' => $ops,
			'options' => $options,
			'writeOptions' => $writeOptions,
			'documents' => $documents
		], $timer);
	}

	/**
	 * MongoDB collection object getter
	 *
	 * @return Collection
	 */
	public function collection(): Collection
	{
		if (!$this->getCollectionName())
		{
			throw new DatabaseException(
				'Cannot use database collection without first setting collection name',
				$this
			);
		}

		return $this->database()->{$this->getCollectionName()};
	}

	/**
	 * Collection field object getter
	 *
	 * @param string $field dot notation supported
	 * @return Field
	 */
	public function collectionField(string $field): Field
	{
		return new Field($field, $this, function (
			$results,
			string $message,
			array $context,
			Timer $timer
		)
		{
			return $this->debug($results, $message, $context, $timer);
		});
	}

	/**
	 * Connection object getter
	 *
	 * @return Connection
	 */
	public function connection(): Connection
	{
		return $this->connection;
	}

	/**
	 * Ref fk constraint
	 *
	 * @param array $documents
	 * @return void
	 */
	private function constraintRefFk(array $documents): void
	{
		if (!$this->model->schema()->hasConstraint(Constraint::TYPE_REF_FK))
		{
			return;
		}

		$options = [];
		$this->optionsReadConcern($options);

		foreach ($this->model->schema()->getConstraints(Constraint::TYPE_REF_FK) as $c)
		{
			/** @var \Lark\Database\Constraint\RefFk $c */
			$c->verify(
				$this,
				$documents,
				$options,
				function (
					$results,
					string $message,
					array $context
				)
				{
					return $this->debug($results, $message, $context);
				}
			);
		}
	}

	/**
	 * Ref delete constraint
	 *
	 * @param array $ids
	 * @return integer Affected
	 */
	private function constraintRefDelete(array $ids): int
	{
		if (
			!$this->hasModel()
			|| !$this->model->schema()->hasConstraint(Constraint::TYPE_REF_DELETE)
		)
		{
			// no ref delete constraint
			return 0;
		}

		$options = [];
		$this->optionsReadConcern($options);
		$this->optionsWriteConcern($options);
		$aff = 0;

		foreach ($this->model->schema()->getConstraints(Constraint::TYPE_REF_DELETE) as $c)
		{
			/** @var \Lark\Database\Constraint\RefDelete $c */
			$aff += $c->delete(
				$this,
				$ids,
				$options,
				function (
					$results,
					string $message,
					array $context
				)
				{
					return $this->debug($results, $message, $context);
				}
			);
		}

		return $aff;
	}

	/**
	 * Count documents matching filter
	 *
	 * @param array $filter
	 * @param array $options
	 * @return integer
	 */
	public function count(array $filter = [], array $options = []): int
	{
		$this->optionsReadConcern($options);
		Convert::inputIdToObjectId($filter);
		$timer = new Timer;

		return $this->debug(
			$this->collection()->countDocuments($filter, $options),
			__METHOD__,
			[
				'filter' => $filter,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Create collection
	 *
	 * @param Schema $schema
	 * @return boolean
	 */
	public function create(Schema $schema = null): bool
	{
		if ($this->hasModel())
		{
			$schema = $this->model->schema();
		}

		if (!$schema)
		{
			throw new DatabaseException('Cannot create collection, invalid schema (empty)', $this);
		}

		$isCreated = false;
		try
		{
			$res = Convert::bsonDocToArray(
				$this->database()->createCollection($this->getCollectionName())
			);

			$isCreated = (bool)$res['ok'] ?? false;
		}
		catch (CommandException $ex)
		{
			throw new DatabaseException($ex->getMessage(), $this);
		}

		if (!$isCreated)
		{
			return false;
		}

		$indexes = $schema->getIndexes();

		if ($indexes)
		{
			$this->collection()->createIndexes($indexes);
		}

		return true;
	}

	/**
	 * MongoDB database object getter
	 *
	 * @return \MongoDB\Database
	 */
	public function database(): \MongoDB\Database
	{
		return $this->connection->client()->{$this->getDatabaseName()};
	}

	/**
	 * Debug decorator
	 *
	 * @param mixed $results
	 * @param string $message
	 * @param array $context
	 * @param Timer|null $timer
	 * @return mixed
	 */
	private function debug($results, string $message, array $context = [], Timer $timer = null)
	{
		$elapsed = null;

		if (
			$this->connection()->getOptions()[ConnectionOptions::DEBUG_DUMP]
			|| $this->connection()->getOptions()[ConnectionOptions::DEBUG_LOG]
		)
		{
			$context = [
				'connectionId' => $this->connection()->getId(),
				'database' => $this->getDatabaseName(),
				'collection' => $this->getCollectionName(),
			] + $context;

			if ($timer)
			{
				$elapsed =  $timer->elapsed();
				$context = ['elapsed' => $elapsed] + $context;
			}
		}

		// log
		if ($this->connection()->getOptions()[ConnectionOptions::DEBUG_LOG])
		{
			(new Logger('$lark'))->debug($message, $context);
		}

		// dump
		if ($this->connection()->getOptions()[ConnectionOptions::DEBUG_DUMP])
		{
			Debugger::internal(
				$message . ($elapsed ? ' (' . $elapsed . ')' : null),
				$context,
				true,
				true
			);
		}

		return $results;
	}

	/**
	 * Delete documents matching filter
	 *
	 * @param array $filter
	 * @param array $options
	 * @return integer Affected
	 */
	public function delete(array $filter, array $options = []): int
	{
		$this->optionsWriteConcern($options);
		Convert::inputIdToObjectId($filter);
		$timer = new Timer;

		if (!$filter)
		{
			throw new DatabaseException('Filter cannot be empty for this method', $this);
		}

		$aff = 0;

		if (
			($res = $this->collection()->deleteMany($filter, $options))
			&& ($res = $res->getDeletedCount())
		)
		{
			$aff = (int)$res;
		}

		return $this->debug(
			$aff,
			__METHOD__,
			[
				'filter' => $filter,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Delete all documents
	 *
	 * @param array $options
	 * @return integer Affected
	 */
	public function deleteAll(array $options = []): int
	{
		$this->optionsWriteConcern($options);
		$timer = new Timer;
		$aff = 0;

		if (
			($res = $this->collection()->deleteMany([], $options))
			&& ($res = $res->getDeletedCount())
		)
		{
			$aff = (int)$res;
		}

		return $this->debug(
			$aff,
			__METHOD__,
			['options' => $options],
			$timer
		);
	}

	/**
	 * Delete documents by ID
	 *
	 * @param array $ids
	 * @param array $options
	 * @return integer Affected
	 */
	public function deleteIds(array $ids, array $options = []): int
	{
		$this->optionsWriteConcern($options);
		$timer = new Timer;

		if (!$ids)
		{
			return $this->debug(0, __METHOD__ . ' ($ids is empty, nothing to do)');
		}

		return $this->debug(
			$this->delete([
				'_id' => [
					'$in' => Convert::idsToObjectIds($ids)
				]
			], $options) + $this->constraintRefDelete($ids),
			__METHOD__,
			[
				'ids' => $ids,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Delete single document matching filter
	 *
	 * @param array $filter
	 * @param array $options
	 * @return integer Affected
	 */
	public function deleteOne(array $filter, array $options = []): int
	{
		$this->optionsWriteConcern($options);
		Convert::inputIdToObjectId($filter);
		$timer = new Timer;

		if (!$filter)
		{
			throw new DatabaseException('Filter cannot be empty for this method', $this);
		}

		$aff = 0;

		if (
			($res = $this->collection()->deleteOne($filter, $options))
			&& ($res = $res->getDeletedCount())
		)
		{
			$aff = (int)$res;
		}

		return $this->debug(
			$aff,
			__METHOD__,
			[
				'filter' => $filter,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Drop collection
	 *
	 * @return boolean
	 */
	public function drop(): bool
	{
		$timer = new Timer;
		$doc = $this->database()->dropCollection($this->getCollectionName());

		return $this->debug(
			$doc->ok == 1,
			__METHOD__,
			[],
			$timer
		);
	}

	/**
	 * Execute command
	 *
	 * @param Command $command
	 * @return Cursor
	 */
	public function executeCommand(Command $command): Cursor
	{
		$timer = new Timer;

		return $this->debug(
			$this->connection()->client()->getManager()->executeCommand(
				$this->getDatabaseName(),
				$command
			),
			__METHOD__,
			[],
			$timer
		);
	}

	/**
	 * Check if collection exists
	 *
	 * @return boolean
	 */
	public function exists(): bool
	{
		$timer = new Timer;

		return $this->debug(
			in_array($this->getCollectionName(), $this->getCollections()),
			__METHOD__,
			[],
			$timer
		);
	}

	/**
	 * Find documents matching filter
	 *
	 * @param array $filter
	 * @param array $options
	 * @return array
	 */
	public function find(array $filter = [], array $options = []): array
	{
		$this->optionsFind($options);
		$this->optionsReadConcern($options);
		$timer = new Timer;

		Convert::inputIdToObjectId($filter);

		return $this->debug(
			Convert::cursorToArray(
				$this->collection()->find($filter, $options)
			),
			__METHOD__,
			[
				'filter' => $filter,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Find document by ID
	 *
	 * @param string|int $id
	 * @param array $options
	 * @return array|null
	 */
	public function findId($id, array $options = []): ?array
	{
		$timer = new Timer;

		if (!$id)
		{
			throw new DatabaseException('Invalid $id, cannot be empty');
		}

		return $this->debug(
			$this->findOne(['_id' => Convert::idToObjectId($id)], $options),
			__METHOD__,
			[
				'id' => $id,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Find documents by ID
	 *
	 * @param array $ids
	 * @param array $options
	 * @return array
	 */
	public function findIds(array $ids, array $options = []): array
	{
		$timer = new Timer;

		if (!$ids)
		{
			return $this->debug([], __METHOD__ . ' ($ids is empty, nothing to do)');
		}

		return $this->debug(
			$this->find([
				'_id' => [
					'$in' => Convert::idsToObjectIds($ids)
				]
			], $options),
			__METHOD__,
			[
				'ids' => $ids,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Find single document matching filter
	 *
	 * @param array $filter
	 * @param array $options
	 * @return array|null
	 */
	public function findOne(array $filter = [], array $options = []): ?array
	{
		$this->optionsFind($options);
		$this->optionsReadConcern($options);
		Convert::inputIdToObjectId($filter);
		$timer = new Timer;

		return $this->debug(
			Convert::bsonDocToArray(
				$this->collection()->findOne($filter, $options)
			),
			__METHOD__,
			[
				'filter' => $filter,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Collection name getter
	 *
	 * @return string
	 */
	public function getCollectionName(): string
	{
		return $this->collection;
	}

	/**
	 * Database collection names getter
	 *
	 * @return array
	 */
	public function &getCollections(): array
	{
		$a = [];

		// workaround for bug in db->listCollections()
		$cursor = $this->executeCommand(
			new Command([
				'listCollections' => 1,
				'nameOnly' => 1,
				'authorizedCollections' => 1
			])
		);

		if ($cursor)
		{
			foreach ($cursor->toArray() as $v)
			{
				$a[] = $v->name;
			}
		}

		return $a;
	}

	/**
	 * Database name getter
	 *
	 * @return string
	 */
	public function getDatabaseName(): string
	{
		return $this->database;
	}

	/**
	 * Check if documents matching filter exist
	 *
	 * @param array $filter
	 * @param array $options
	 * @return boolean
	 */
	public function has(array $filter, array $options = []): bool
	{
		$timer = new Timer;

		return $this->debug(
			$this->count($filter, $options) > 0,
			__METHOD__,
			[
				'filter' => $filter,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Check if documents with IDs exist
	 *
	 * @param array $ids
	 * @param array $options
	 * @return boolean
	 */
	public function hasIds(array $ids, array $options = []): bool
	{
		$timer = new Timer;

		if (!$ids)
		{
			return $this->debug(false, __METHOD__ . ' ($ids is empty, nothing to do)');
		}

		return $this->debug(
			$this->has([
				'_id' => [
					'$in' => Convert::idsToObjectIds($ids)
				]
			]),
			__METHOD__,
			[
				'ids' => $ids,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Check if model exists
	 *
	 * @return boolean
	 */
	public function hasModel(): bool
	{
		if (!isset($this->model))
		{
			return false;
		}

		return $this->model !== null;
	}

	/**
	 * Insert documents
	 *
	 * @param array $documents
	 * @param array $options
	 * @return array Document IDs
	 */
	public function insert(array $documents, array $options = []): array
	{
		$this->optionsWriteConcern($options);
		$timer = new Timer;

		if (!$documents) // avoid MongoDB exception "$documents is empty"
		{
			return $this->debug([], __METHOD__ . ' ($documents is empty, nothing to do)');
		}

		if ($this->hasModel())
		{
			$documents = $this->model->makeArray($documents);
			$this->constraintRefFk($documents);
		}

		Convert::inputIdToObjectIdArray($documents);
		$ids = [];

		if (
			($res = $this->collection()->insertMany($documents, $options))
			&& ($res = $res->getInsertedIds())
		)
		{
			foreach ($res as $v)
			{
				$ids[] = Convert::objectIdToString($v);
			}
		}

		return $this->debug(
			$ids,
			__METHOD__,
			[
				'documents' => $documents,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Insert single document
	 *
	 * @param array|object $document
	 * @param array $options
	 * @return string|null Document ID
	 */
	public function insertOne($document, array $options = []): ?string
	{
		$this->optionsWriteConcern($options);
		Convert::inputIdToObjectId($document);
		$timer = new Timer;

		if (!$document) // empty document
		{
			return $this->debug(null, __METHOD__ . ' ($document is empty, nothing to do)');
		}

		if ($this->hasModel())
		{
			$document = $this->model->make($document);
			$this->constraintRefFk([$document]);
		}

		$id = null;

		if (
			($res = $this->collection()->insertOne($document, $options))
			&& ($res = $res->getInsertedId())
		)
		{
			$id = Convert::objectIdToString($res);
		}

		return $this->debug(
			$id,
			__METHOD__,
			[
				'document' => $document,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Convert ID to object ID
	 *
	 * @param string|null $id
	 * @return string|null|ObjectId
	 */
	public function objectId(?string $id)
	{
		return Convert::idToObjectId($id);
	}

	/**
	 * Apply connection find options to find options
	 *
	 * @param array $options
	 * @return void
	 */
	private function optionsFind(array &$options): void
	{
		// auto limit
		if (
			!isset($options['limit'])
			&& $this->connection()->getOptions()[ConnectionOptions::FIND_LIMIT] > 0
		)
		{
			$options['limit'] = $this->connection()->getOptions()[ConnectionOptions::FIND_LIMIT];
		}

		$this->optionsModelFilter($options);
	}

	/**
	 * Auto projection from model schema $filter
	 *
	 * @param array $options
	 * @return void
	 */
	private function optionsModelFilter(array &$options): void
	{
		if (!$this->hasModel())
		{
			return;
		}

		// auto projection from $filter
		if (
			!isset($options['projection'])
			&& $this->model->schema()->hasFilter()
		)
		{
			$options['projection'] = $this->model->schema()->getFilter();
		}
	}

	/**
	 * Apply connection read concern options
	 *
	 * @param array $options
	 * @return void
	 */
	private function optionsReadConcern(array &$options): void
	{
		if (
			!isset($options['readConcern'])
			&& $this->connection()->getOptions()[ConnectionOptions::READ_CONCERN]
		)
		{
			$options['readConcern'] =
				$this->connection()->getOptions()[ConnectionOptions::READ_CONCERN];
		}
	}

	/**
	 * Apply connection write concern options
	 *
	 * @param array $options
	 * @return void
	 */
	private function optionsWriteConcern(array &$options): void
	{
		if (
			!isset($options['writeConcern'])
			&& $this->connection()->getOptions()[ConnectionOptions::WRITE_CONCERN]
		)
		{
			$options['writeConcern'] =
				$this->connection()->getOptions()[ConnectionOptions::WRITE_CONCERN];
		}
	}

	/**
	 * Ping command
	 *
	 * @return boolean
	 */
	public function ping(): bool
	{
		$timer = new Timer;
		$isOk = false;
		$context = [];

		try
		{
			$cursor = $this->executeCommand(
				new Command(['ping' => 1]),
			);

			$isOk = ($cursor->toArray()[0]->ok ?? null) == 1;
		}
		catch (ConnectionTimeoutException $ex)
		{
			$context = [
				'connectionTimeout' => true,
				'exceptionMessage' => $ex->getMessage()
			];
		}

		return $this->debug($isOk, __METHOD__, $context, $timer);
	}

	/**
	 * Bulk replace
	 *
	 * @param array $documents
	 * @param array $options
	 * @param bool $returnAffected Return affected count like `[affected, [ids]]`
	 * @return array Document IDs or affected count with IDs like `[affected, [ids]]`
	 */
	public function replaceBulk(
		array $documents,
		array $options = [],
		$returnAffected = false
	): array
	{
		$this->optionsWriteConcern($options);
		$timer = new Timer;

		if ($this->hasModel())
		{
			$documents = $this->model->makeArray($documents, Validator::MODE_REPLACE_ID);
			$this->constraintRefFk($documents);
		}

		return $this->debug(
			$returnAffected
				? $this->bulkWrite('replaceOne', $documents, $options)
				: $this->bulkWrite('replaceOne', $documents, $options)[1],
			__METHOD__,
			[
				'documents' => $documents,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Replace document by ID
	 *
	 * @param string|int $id
	 * @param array|object $document
	 * @param array $options
	 * @return array|null Replaced document
	 */
	public function replaceId($id, $document, array $options = []): ?array
	{
		if (!$id)
		{
			throw new DatabaseException('Invalid $id, cannot be empty');
		}

		$timer = new Timer;

		return $this->debug(
			$this->replaceOne(['_id' => Convert::idToObjectId($id)], $document, $options),
			__METHOD__,
			[
				'id' => $id,
				'document' => $document,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Replace single document
	 *
	 * @param array $filter
	 * @param array|object $document
	 * @param array $options
	 * @return array|null Replaced document
	 */
	public function replaceOne(array $filter, $document, array $options = []): ?array
	{
		$this->optionsModelFilter($options);
		$this->optionsReadConcern($options);
		$this->optionsWriteConcern($options);
		Convert::inputIdToObjectId($filter);
		$timer = new Timer;

		// return the new doc
		$options = [
			'returnDocument' => FindOneAndReplace::RETURN_DOCUMENT_AFTER
		] + $options;

		if ($this->hasModel())
		{
			$document = $this->model->make($document, Validator::MODE_REPLACE);
			$this->constraintRefFk([$document]);
		}

		$doc = $this->collection()->findOneAndReplace($filter, $document, $options);

		return $this->debug(
			$doc ? Convert::bsonDocToArray($doc) : null,
			__METHOD__,
			[
				'filter' => $filter,
				'document' => $document,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Collection name setter
	 *
	 * @param string $name
	 * @return void
	 */
	private function setCollection(string $name): void
	{
		$name = trim($name);

		if (!$name)
		{
			throw new DatabaseException('Invalid collection name (empty)', $this);
		}

		$this->collection = $name;
	}

	/**
	 * Database name setter
	 *
	 * @param string $name
	 * @return void
	 */
	private function setDatabase(string $name): void
	{
		$name = trim($name);

		if (!$name)
		{
			throw new DatabaseException('Invalid database name (empty)', $this);
		}

		// check db whitelist
		if (
			$this->connection()->getOptions()[ConnectionOptions::DB_ALLOW]
			&& !in_array($name, $this->connection()->getOptions()[ConnectionOptions::DB_ALLOW])
		)
		{
			throw new DatabaseException('Database "' . $name . '" not allowed', $this);
		}

		// check db blacklist
		if (
			$this->connection()->getOptions()[ConnectionOptions::DB_DENY]
			&& in_array($name, $this->connection()->getOptions()[ConnectionOptions::DB_DENY])
		)
		{
			throw new DatabaseException('Database "' . $name . '" is restricted', $this);
		}

		$this->database = $name;
	}

	/**
	 * Update documents matching filter
	 *
	 * @param array $filter
	 * @param array|object $update
	 * @param array $options
	 * @return integer Affected
	 */
	public function update(array $filter, $update, array $options = []): int
	{
		$this->optionsWriteConcern($options);
		Convert::inputIdToObjectId($filter);
		$timer = new Timer;

		// check for empty update
		if (
			is_array($update) && !$update
			|| is_object($update) && !get_object_vars($update)
		)
		{
			return $this->debug(0, __METHOD__ . ' ($update is empty, nothing to do)');
		}

		if ($this->hasModel())
		{
			$update = $this->model->make($update, Validator::MODE_UPDATE);
			$this->constraintRefFk([$update]);
		}

		$aff = 0;

		if (
			($res = $this->collection()->updateMany($filter, [
				'$set' => $update
			], $options))
			&& ($res = $res->getModifiedCount())
		)
		{
			$aff = (int)$res;
		}

		return $this->debug(
			$aff,
			__METHOD__,
			[
				'filter' => $filter,
				'update' => $update,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Bulk update
	 *
	 * @param array $documents
	 * @param array $options
	 * @param bool $returnAffected Return affected count like `[affected, [ids]]`
	 * @return array Document IDs or affected count with IDs like `[affected, [ids]]`
	 */
	public function updateBulk(
		array $documents,
		array $options = [],
		$returnAffected = false
	): array
	{
		$this->optionsWriteConcern($options);
		$timer = new Timer;

		if ($this->hasModel())
		{
			$documents = $this->model->makeArray($documents, Validator::MODE_UPDATE_ID);
			$this->constraintRefFk($documents);
		}

		return $this->debug(
			$returnAffected
				? $this->bulkWrite('updateOne', $documents, $options)
				: $this->bulkWrite('updateOne', $documents, $options)[1],
			__METHOD__,
			[
				'documents' => $documents,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Update document by ID
	 *
	 * @param string|int $id
	 * @param array|object $update
	 * @param array $options
	 * @return array|null Updated document
	 */
	public function updateId($id, $update, array $options = []): ?array
	{
		if (!$id)
		{
			throw new DatabaseException('Invalid $id, cannot be empty');
		}

		$this->optionsWriteConcern($options);
		$timer = new Timer;

		return $this->debug(
			$this->updateOne(['_id' => Convert::idToObjectId($id)], $update, $options),
			__METHOD__,
			[
				'id' => $id,
				'update' => $update,
				'options' => $options
			],
			$timer
		);
	}

	/**
	 * Update single document matching filter
	 *
	 * @param array $filter
	 * @param array|object $update
	 * @param array $options
	 * @return array|null Update document
	 */
	public function updateOne(array $filter, $update, array $options = []): ?array
	{
		$this->optionsModelFilter($options);
		$this->optionsReadConcern($options);
		$this->optionsWriteConcern($options);
		Convert::inputIdToObjectId($filter);
		$timer = new Timer;

		// return the new doc
		$options = [
			'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER
		] + $options;

		// check for empty update
		if (
			is_array($update) && !$update
			|| is_object($update) && !get_object_vars($update)
		)
		{
			return $this->debug(null, __METHOD__ . ' ($update is empty, nothing to do)');
		}

		if ($this->hasModel())
		{
			$update = $this->model->make($update, Validator::MODE_UPDATE);
			$this->constraintRefFk([$update]);
		}

		$doc = $this->collection()->findOneAndUpdate($filter, ['$set' => $update], $options);

		return $this->debug(
			$doc ? Convert::bsonDocToArray($doc) : null,
			__METHOD__,
			[
				'filter' => $filter,
				'update' => $update,
				'options' => $options
			],
			$timer
		);
	}
}
