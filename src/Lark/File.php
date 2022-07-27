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

/**
 * File
 *
 * @author Shay Anderson
 */
class File
{
	/**
	 * Path
	 *
	 * @var string
	 */
	private string $path;

	/**
	 * Init
	 *
	 * @param string $path
	 */
	public function __construct(string $path)
	{
		$this->path = $path;
	}

	/**
	 * Delete file
	 *
	 * @return boolean
	 */
	public function delete(): bool
	{
		$this->existsOrException();

		return unlink($this->path());
	}

	/**
	 * Check if file exists
	 *
	 * @return boolean
	 */
	public function exists(): bool
	{
		return file_exists($this->path()) && is_file($this->path());
	}

	/**
	 * Check if file exists, if not throw exception
	 *
	 * @return void
	 * @throws Exception
	 */
	public function existsOrException(): void
	{
		if (!$this->exists())
		{
			throw new Exception('File does not exist', ['path' => $this->path()]);
		}
	}

	/**
	 * Path getter
	 *
	 * @return string
	 */
	public function path(): string
	{
		if (!isset($this->path))
		{
			throw new Exception('Invalid path (empty)');
		}

		return $this->path;
	}

	/**
	 * File contents getter
	 *
	 * @return string
	 */
	public function read(): string
	{
		$this->existsOrException();
		return file_get_contents($this->path());
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
		// set flags
		$flags = 0;

		if ($append)
		{
			$flags |= FILE_APPEND;
		}

		if ($lock)
		{
			$flags |= LOCK_EX;
		}

		return file_put_contents($this->path(), $data, $flags) !== false;
	}
}
