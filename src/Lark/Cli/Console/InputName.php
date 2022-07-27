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

use Lark\Cli\CliException;

/**
 * Input name
 *
 * @author Shay Anderson
 */
class InputName
{
	/**
	 * Name
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * Namespace
	 *
	 * @var array
	 */
	private array $namespace;

	/**
	 * Subdirectory
	 *
	 * @var string|null
	 */
	private ?string $subdir = null;

	/**
	 * Init
	 *
	 * @param string $inputName
	 */
	public function __construct(string $inputName)
	{
		if (!$inputName)
		{
			throw new CliException('Input name cannot be empty');
		}

		$parts = explode('/', trim($inputName));

		$this->name = array_pop($parts);
		$this->validate($this->name);

		$this->namespace = [];
		foreach ($parts as $part)
		{
			$this->validate($part);
			$this->namespace[] = $part;
		}

		if ($this->namespace)
		{
			$this->subdir = '/' . implode('/', $this->namespace);
		}
	}

	/**
	 * Name getter
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Namespace getter
	 *
	 * @return array
	 */
	public function getNamespace(): array
	{
		return $this->namespace;
	}

	/**
	 * Namespace and name getter
	 *
	 * @return string
	 */
	public function getNamespaceName(): string
	{
		return ($this->namespace ? implode('/', $this->namespace) . '/' : null)
			. $this->name;
	}

	/**
	 * Subdirectory getter
	 *
	 * @return string|null
	 */
	public function getSubdir(): ?string
	{
		return $this->subdir;
	}

	/**
	 * Check if namespace exists
	 *
	 * @return boolean
	 */
	public function hasNamespace(): bool
	{
		return count($this->namespace) > 0;
	}

	/**
	 * Validate name
	 *
	 * @param string $name
	 * @return void
	 */
	private function validate(string $name): void
	{
		// from RFC 3986, do not allow '/' for path part
		$allowed = 'a-zA-Z0-9.-_~!$&\'()*+,;=:@';
		$allowedEscaped = 'a-zA-Z0-9\.\-_~\!\$&\'\(\)\*\+,;\=\:@';

		if (preg_match('/^[' . $allowedEscaped . ']+$/', $name) !== 1)
		{
			throw new CliException(
				'Invalid name, name can only contain characters "' . $allowed . '"'
			);
		}
	}
}
