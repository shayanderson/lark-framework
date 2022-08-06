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

	/**
	 * Format input path name, add file extension if needed
	 *
	 * @param string $pathName
	 * @return string
	 */
	public static function formatPathName(string $pathName): string
	{
		$pathName = trim($pathName);

		if (substr($pathName, -4) !== '.php')
		{
			$pathName .= '.php';
		}

		return $pathName;
	}

	/**
	 * Relative path for schema directory file
	 *
	 * @return string
	 */
	public function pathRelativeSchemaDir(): string
	{
		return self::absolutePathToRelative($this->path(), DIR_SCHEMAS);
	}
}
