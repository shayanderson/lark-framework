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

use Lark\Database;
use Lark\Database\Connection;
use Lark\Schema;

/**
 * Revision DB model
 *
 * @author Shay Anderson
 */
class RevisionModel extends \Lark\Model
{
	/**
	 * Collection name
	 */
	const COLLECTION = '#revs';

	/**
	 * Field names
	 */
	const FIELD_ID = 'id';
	const FIELD_REV_ID = 'revId';
	const FIELD_COLL = 'coll';
	const FIELD_STATUS = 'status';
	const FIELD_DT = 'dt';
	const FIELD_CREATED = 'created';

	/**
	 * Statuses
	 */
	const STATUS_PENDING = 0;
	const STATUS_COMMITTED = 1;
	const STATUS_FAILED = 2;

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
	 * Revision ID
	 *
	 * @var string
	 */
	private string $revId;

	/**
	 * Init
	 *
	 * @param string $connectionId
	 * @param string $database
	 * @param string $collection
	 * @param string $revId
	 */
	public function __construct(
		string $connectionId,
		string $database,
		string $collection,
		string $revId
	)
	{
		$this->connectionId = $connectionId;
		$this->database = $database;
		$this->collection = $collection;
		$this->revId = $revId;
	}

	/**
	 * Create revision collection
	 *
	 * @return boolean
	 */
	public function createRevCollection(): bool
	{
		if (!$this->getRevDb()->exists())
		{
			return $this->getRevDb()->create(self::schema());
		}

		return true;
	}

	/**
	 * Delete revision
	 *
	 * @return integer (aff)
	 */
	public function delete(): int
	{
		return $this->getRevDb()->deleteOne([self::FIELD_REV_ID => $this->revId]);
	}

	/**
	 * Database getter
	 *
	 * @return Database
	 */
	public function getDb(): Database
	{
		return Connection::factory(
			$this->connectionId . '$' . $this->database . '$' . $this->collection
		);
	}

	/**
	 * Revision database getter
	 *
	 * @return Database
	 */
	private function getRevDb(): Database
	{
		return Connection::factory(
			$this->connectionId . '$' . $this->database . '$' . self::COLLECTION
		);
	}

	/**
	 * Pending revisions getter
	 *
	 * @param integer $dtSort
	 * @return array
	 */
	public function getPendingRevs(int $dtSort = 1): array
	{
		return $this->getRevDb()->find([
			self::FIELD_STATUS => self::STATUS_PENDING
		], [
			'sort' => [self::FIELD_DT => $dtSort],
			'limit' => 5_000
		]);
	}

	/**
	 * Check if revision exists
	 *
	 * @return boolean
	 */
	public function hasRev(): bool
	{
		return $this->getRevDb()->has([self::FIELD_REV_ID => $this->revId]);
	}

	/**
	 * Insert revision
	 *
	 * @return string
	 */
	public function insertRev(): string
	{
		$this->createRevCollection(); // auto create collection
		return $this->getRevDb()->insertOne(
			$this->make([
				self::FIELD_REV_ID => $this->revId,
				self::FIELD_COLL => $this->collection,
				self::FIELD_DT => self::parseDtFromRevId($this->revId)
			])
		);
	}

	/**
	 * Datetime from revision ID getter
	 *
	 * @param string $revId
	 * @return integer
	 */
	private static function parseDtFromRevId(string $revId): int
	{
		if (preg_match('/^([0-9]{17})\$/', $revId, $m))
		{
			return (int)$m[0];
		}

		throw new ConsoleException('Failed to parse DT from revId "' . $revId . '"');
	}

	/**
	 * Schema getter
	 *
	 * @return Schema
	 */
	public static function schema(): Schema
	{
		return new Schema([
			// indexes
			'$indexes' => [
				[self::FIELD_REV_ID => 1, '$name' => 'idxRevId', '$unique' => true],
				[self::FIELD_DT => 1, '$name' => 'idxDt'],
				[
					self::FIELD_DT => 1, self::FIELD_COLL => 1, '$name' => 'idxDtColl',
					'$unique' => true
				],
			],
			// fields
			self::FIELD_ID => ['string', 'id'],
			self::FIELD_REV_ID => ['string', 'notEmpty'],
			self::FIELD_COLL => ['string', 'notEmpty'],
			self::FIELD_STATUS => [
				'int',
				['between' => [0, 3]],
				['default' => self::STATUS_PENDING]
			],
			self::FIELD_DT => ['int', 'notEmpty'],
			self::FIELD_CREATED => ['dbdatetime', 'notNull', ['default' => db_datetime()]]
		]);
	}

	/**
	 * Update revision status
	 *
	 * @param string $revId
	 * @param integer $status
	 * @return void
	 */
	public function updateRevStatus(string $revId, int $status): void
	{
		$this->getRevDb()->updateOne([
			self::FIELD_REV_ID => $revId
		], [
			self::FIELD_STATUS => $status
		]);
	}
}
