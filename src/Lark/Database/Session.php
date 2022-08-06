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

use Lark\Debugger;
use Lark\Factory\Singleton;
use Lark\Model;
use MongoDB\BSON\Binary;
use MongoDB\BSON\UTCDateTime;
use SessionHandlerInterface;

/**
 * Database session handler
 *
 * @author Shay Anderson
 */
class Session extends Singleton implements SessionHandlerInterface
{
	/**
	 * Session ID
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * Model
	 *
	 * @var Model
	 */
	private Model $model;

	/**
	 * Init
	 *
	 * @return void
	 */
	protected function __init(): void
	{
		// register as session handler
		session_set_save_handler($this);
	}

	/**
	 * @inheritDoc
	 */
	public function close(): bool
	{
		// auto handled by database connection
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function destroy($id): bool
	{
		Debugger::internal(__METHOD__, ['id' => $id]);

		$this->model->db()->deleteIds([$id]);

		return true;
	}

	/**
	 * @inheritDoc
	 */
	#[\ReturnTypeWillChange]
	public function gc($maxLifetime)
	{
		$rmBefore = new UTCDateTime(
			floor((microtime(true) - $maxLifetime) * 1000)
		);

		Debugger::internal(__METHOD__, [
			'maxLifetime' => $maxLifetime,
			'rmBefore' => $rmBefore
		]);

		$this->model->db()->delete(['access' => ['$lt' => $rmBefore]]);

		return true;
	}

	/**
	 * Set as handler
	 *
	 * @param Model $model
	 * @return void
	 */
	public static function handler(Model $model): void
	{
		Debugger::internal(__METHOD__, ['model' => get_class($model)]);

		/** @var Session $sess */
		$sess = self::getInstance();
		$sess->setModel($model);
	}

	/**
	 * @inheritDoc
	 */
	public function open($path, $name): bool
	{
		// auto handled by database connection
		return true;
	}

	/**
	 * @inheritDoc
	 */
	#[\ReturnTypeWillChange]
	public function read($id)
	{
		Debugger::internal(__METHOD__, ['id' => $id]);

		$this->id = $id;
		$doc = $this->model->db()->findId($id);

		if (!$doc)
		{
			return '';
		}

		return $doc['data']->getData();
	}

	/**
	 * Model setter
	 *
	 * @param Model $model
	 * @return void
	 */
	public function setModel(Model $model): void
	{
		$this->model = $model;
	}

	/**
	 * @inheritDoc
	 */
	public function write($id, $data): bool
	{
		Debugger::internal(__METHOD__, ['id' => $id, 'data' => $data]);

		if (!isset($this->id) || $this->id !== $id)
		{
			$this->id = $id;
		}

		$doc = [
			'_id' => $this->id,
			'data' => new Binary($data, Binary::TYPE_GENERIC),
			'access' => new UTCDateTime
		];

		$this->model->db()->replaceOne(['id' => $this->id], $doc, ['upsert' => true]);

		return true;
	}
}
