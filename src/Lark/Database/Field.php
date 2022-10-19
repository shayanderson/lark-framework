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

use Closure;
use Lark\Database;
use Lark\Timer;

/**
 * Database field
 *
 * @author Shay Anderson
 */
class Field
{
	/**
	 * Database object
	 *
	 * @var Database
	 */
	private Database $database;

	/**
	 * Debug callback
	 *
	 * @var Closure
	 */
	private Closure $debugCallback;

	/**
	 * Field name
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * Init
	 *
	 * @param string $name
	 * @param Database $database
	 * @param Closure $debugCallback
	 */
	public function __construct(string $name, Database &$database, Closure $debugCallback)
	{
		$this->name = $name;
		$this->database = &$database;
		$this->debugCallback = $debugCallback;
	}

	/**
	 * Create a new field
	 *
	 * @param mixed $defaultValue
	 * @return integer (affected)
	 */
	public function create($defaultValue = null): int
	{
		$timer = new Timer;

		return $this->debug(
			$this->database->update(
				[],
				[
					$this->name => $defaultValue
				]
			),
			__METHOD__,
			[
				'defaultValue' => $defaultValue
			],
			$timer
		);
	}

	/**
	 * Debug
	 *
	 * @param mixed $results
	 * @param string $message
	 * @param array $context
	 * @param Timer $timer
	 * @return mixed
	 */
	private function debug($results, string $message, array $context = [], Timer $timer)
	{
		return ($this->debugCallback)(
			$results,
			$message,
			['field' => $this->name] + $context,
			$timer
		);
	}

	/**
	 * Delete collection field
	 *
	 * @return integer (affected)
	 */
	public function delete(): int
	{
		$timer = new Timer;

		$aff = 0;

		if (
			($res = $this->database->collection()->updateMany([], [
				'$unset' => [
					$this->name => ''
				]
			]))
			&& ($res = $res->getModifiedCount())
		)
		{
			$aff = (int)$res;
		}

		return $this->debug(
			$aff,
			__METHOD__,
			[],
			$timer
		);
	}

	/**
	 * Check if field exists
	 *
	 * @param boolean  $allDocs will check if field exists on all documents, otherwise any document
	 * @return boolean
	 */
	public function exists(bool $allDocs = true): bool
	{
		$timer = new Timer;

		if (!$allDocs)
		{
			$exists = $this->database->has([
				$this->name => ['$exists' => true]
			]);
		}
		else
		{
			$exists = !$this->database->has([
				$this->name => ['$exists' => false]
			]);
		}

		return $this->debug(
			$exists,
			__METHOD__,
			[
				'allDocs' => $allDocs
			],
			$timer
		);
	}

	/**
	 * Remove value from array
	 *
	 * @param array $filter
	 * @param mixed $value
	 * @return integer (affected)
	 */
	public function pull(array $filter, $value): int
	{
		Convert::inputIdToObjectId($filter);
		$timer = new Timer;
		$aff = 0;

		if (
			($res = $this->database->collection()->updateMany($filter, [
				'$pull' => [$this->name => is_array($value) ? ['$in' => $value] : $value]
			]))
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
				'value' => $value
			],
			$timer
		);
	}

	/**
	 * Append value to array
	 *
	 * @param array $filter
	 * @param mixed $value
	 * @param boolean $unique will only append value if doens't already exists in array
	 * @return integer (affected)
	 */
	public function push(array $filter, $value, $unique = true): int
	{
		Convert::inputIdToObjectId($filter);
		$timer = new Timer;
		$aff = 0;
		$op = $unique ? '$addToSet' : '$push';

		if (
			($res = $this->database->collection()->updateMany($filter, [
				$op => [$this->name => is_array($value) ? ['$each' => $value] : $value]
			]))
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
				'value' => $value
			],
			$timer
		);
	}

	/**
	 * Rename field
	 *
	 * @param string $newName
	 * @return integer (affected)
	 */
	public function rename(string $newName): int
	{
		$timer = new Timer;

		$aff = 0;

		if (
			($res = $this->database->collection()->updateMany([], [
				'$rename' => [
					$this->name => $newName
				]
			]))
			&& ($res = $res->getModifiedCount())
		)
		{
			$aff = (int)$res;
		}

		return $this->debug(
			$aff,
			__METHOD__,
			[
				'newName' => $newName
			],
			$timer
		);
	}
}
