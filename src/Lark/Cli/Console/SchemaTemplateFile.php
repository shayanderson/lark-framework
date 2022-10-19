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
use Lark\Json\Decoder as JsonDecoder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Console schema template file
 *
 * @author Shay Anderson
 */
class SchemaTemplateFile extends File
{
	/**
	 * @inheritDoc
	 */
	const TYPE = 'template';

	/**
	 * Compile schema from template
	 *
	 * @return void
	 */
	public function compile(): void
	{
		$schemaFile = SchemaFile::factory($this->getInputName());

		if ($schemaFile->write(
			Template::schema(
				$this->getInputName()->getNamespaceName(),
				$this->readJson(),
				File::absolutePathToRelative($this->path())
			)
		))
		{
			Console::getInstance()->output()->ok(
				'Schema "' . $this->getInputName()->getNamespaceName() . '" compiled',
				''
			);
			Console::getInstance()->output()->dim(' ("' . $schemaFile->path() . '")');
		}
		else
		{
			throw new ConsoleException('Failed to compile schema file', [
				'path' => $schemaFile->path(),
				'templatePath' => $this->path()
			]);
		}
	}

	/**
	 * Compile schemas from all templates
	 *
	 * @return void
	 */
	public static function compileAll(): void
	{
		$dir = new RecursiveDirectoryIterator(
			Console::getDir('template'),
			RecursiveDirectoryIterator::SKIP_DOTS
		);

		foreach (new RecursiveIteratorIterator($dir) as $o)
		{
			$filename = $o->getFilename();

			// match 'schema.*'
			if (preg_match('/^schema\./', $filename))
			{
				$inputName = self::inputNameFromFilePath($o->getPathname());
				$schemaTemplateFile = self::factory($inputName);
				$schemaTemplateFile->compile();
			}
		}
	}

	/**
	 * Input name object to schema template file object
	 *
	 * @param InputName $inputName
	 * @return self
	 */
	public static function factory(InputName $inputName): self
	{
		return new self($inputName, 'schema.' . $inputName->getName() . '.json');
	}

	/**
	 * Input name object from file path getter
	 *
	 * @param string $filePath
	 * @return InputName
	 */
	private static function inputNameFromFilePath(string $filePath): InputName
	{
		$filePathRel = File::absolutePathToRelative($filePath, DIR_TEMPLATES);

		// match: '(subdirs)schema.(name).json'
		if (!preg_match('/^(.*?)schema\.(.*)\.json/', $filePathRel, $m))
		{
			throw new ConsoleException('Failed to parse name from file path', [
				'filePath' => $filePath,
				'filePathRel' => $filePathRel
			]);
		}

		return new InputName($m[1] . $m[2]);
	}

	/**
	 * Read file as JSON (decode)
	 *
	 * @return array
	 */
	public function readJson(): array
	{
		return JsonDecoder::decode(
			parent::read(),
			true
		);
	}

	/**
	 * Write file as JSON (encode)
	 *
	 * @param mixed $template
	 * @param boolean $append
	 * @param boolean $lock
	 * @return boolean
	 */
	public function write($template, $append = false, $lock = true): bool
	{
		return parent::write(
			json_encode($template, JSON_PRETTY_PRINT),
			$append,
			$lock
		);
	}
}
