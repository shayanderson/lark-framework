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

/**
 * Console schema file
 *
 * @author Shay Anderson
 */
class SchemaFile extends File
{
	/**
	 * @inheritDoc
	 */
	const TYPE = 'schema';

	/**
	 * @inheritDoc
	 */
	protected bool $ignoreFileExistsAbort = true;

	/**
	 * Input name object to schema file object
	 *
	 * @param InputName $inputName
	 * @return self
	 */
	public static function factory(InputName $inputName): self
	{
		return new self($inputName, $inputName->getName() . '.php');
	}
}
