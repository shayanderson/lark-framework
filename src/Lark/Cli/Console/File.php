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

use Lark\Cli\Console;

/**
 * Console file
 *
 * @author Shay Anderson
 */
abstract class File extends \Lark\File
{
	/**
	 * File type
	 */
	const TYPE = '';

	/**
	 * Directory
	 *
	 * @var string
	 */
	private string $dir;

	/**
	 * Ignore file exists abort during writes
	 *
	 * @var boolean
	 */
	protected bool $ignoreFileExistsAbort = false;

	/**
	 * Input name
	 *
	 * @var InputName
	 */
	protected InputName $inputName;

	/**
	 * Init
	 *
	 * @param InputName $inputName
	 * @param string $filename
	 */
	public function __construct(InputName $inputName, string $filename)
	{
		$this->dir = self::getDir($inputName);
		parent::__construct($this->dir . '/' . $filename);
		$this->inputName = $inputName;
	}

	/**
	 * Absolute path to relative path
	 *
	 * @param string $path
	 * @param string $basePath
	 * @return string
	 */
	public static function absolutePathToRelative(string $path, string $basePath = DIR_ROOT): string
	{
		return substr($path, strlen($basePath) + 1);
	}

	/**
	 * Directory getter
	 *
	 * @param InputName $name
	 * @return string
	 */
	private static function getDir(InputName $name): string
	{
		if (!static::TYPE)
		{
			throw new ConsoleException('Invalid type (empty)');
		}

		return Console::getDir(static::TYPE) . $name->getSubdir();
	}

	/**
	 * Input name object getter
	 *
	 * @return InputName
	 */
	public function getInputName(): InputName
	{
		return $this->inputName;
	}

	/**
	 * Check if file exists, if it does trigger console error
	 *
	 * @param string|null $path
	 * @param string|null $name
	 * @return void
	 */
	public function fileExistsAbort(string $path = null, string $name = null): void
	{
		if ($this->ignoreFileExistsAbort)
		{
			return;
		}

		if (!$path)
		{
			$path = $this->path();
		}

		if ((new parent($path))->exists())
		{
			Console::getInstance()->error(
				($name ? $name . ' file' : 'File')
					. ' for "' . $this->inputName->getNamespaceName() . '" already exists "'
					. $path . '", command aborted.'
			);
		}
	}

	/**
	 * Relative path getter
	 *
	 * @return string
	 */
	public function pathRelative(): string
	{
		return self::absolutePathToRelative($this->path());
	}

	/**
	 * Write to file
	 *
	 * @param mixed $data
	 * @param boolean $append
	 * @param boolean $lock
	 * @return boolean
	 */
	public function write($data, $append = false, $lock = true): bool
	{
		$this->fileExistsAbort($this->path());

		if (!is_dir($this->dir))
		{
			if (!mkdir($this->dir, 0775, true))
			{
				throw new ConsoleException('Failed to create directory "' . $this->dir . '"');
			}
		}

		$status = parent::write($data, $append, $lock);

		if (!$status)
		{
			throw new ConsoleException('Failed to write "' . $this->inputName->getNamespaceName()
				. '" file "' . $this->path() . '"');
		}

		return $status;
	}
}
