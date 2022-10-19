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
 * Abstract database model schema constraint
 *
 * @author Shay Anderson
 */
abstract class Constraint
{
	/**
	 * Constraint types
	 */
	const TYPE_REF_DELETE = '$ref:delete';
	const TYPE_REF_FK = '$ref:fk';

	/**
	 * Collection name
	 *
	 * @var string
	 */
	private string $collection;

	/**
	 * Init
	 *
	 * @param string $collection
	 */
	public function __construct(string $collection)
	{
		$this->collection = $collection;
	}

	/**
	 * Collection name getter
	 *
	 * @return string
	 */
	public function getCollection(): string
	{
		return $this->collection;
	}
}
