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

use Lark\Validator\ValidatorException;

/**
 * Options class
 *
 * @author Shay Anderson
 */
class Options
{
	/**
	 * Name for options
	 *
	 * @var string|null
	 */
	private ?string $name;

	/**
	 * Options
	 *
	 * @var array
	 */
	private array $options = [];

	/**
	 * Schema
	 *
	 * @var array
	 */
	private array $schema;

	/**
	 * Init
	 *
	 * @param array $schema
	 * @param string|null $name
	 */
	public function __construct(array $schema, ?string $name = null)
	{
		$this->schema = $schema;
		$this->name = $name;

		foreach ($this->schema as $opt => $rules)
		{
			if ($opt === 'fields')
			{
				throw new Exception('Invalid options, schema nested fields are not supported');
			}

			// use default value
			if (array_key_exists('default', $rules))
			{
				$this->options[$opt] = $rules['default'];
				unset($this->schema[$opt]['default']);
			}
		}
	}

	/**
	 * Options validator + getter
	 *
	 * @return array
	 */
	public function make(): array
	{
		try
		{
			return (new Validator($this->options, $this->schema))->make();
		}
		catch (ValidatorException $ex)
		{
			throw new Exception(
				'Invalid option value' . ($this->name ? " ({$this->name})" : ''),
				['error' => $ex->getMessage()]
			);
		}
	}

	/**
	 * Merge options with existing options
	 *
	 * @param array $options (overrides existing options)
	 * @return self
	 */
	public function set(array $options): self
	{
		foreach ($options as $opt => $v)
		{
			if (!isset($this->schema[$opt]))
			{
				throw new Exception('Invalid option "' . $opt . '", option does not exist'
					. ($this->name ? " ({$this->name})" : ''));
			}
		}

		$this->options = $options + $this->options;
		return $this;
	}
}
