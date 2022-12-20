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

use Lark\Database\Constraint\RefFk;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
	 * Schemas refs getter
	 *
	 * @param array $ignoreMissing
	 * @return array
	 */
	public static function getAllRefs(array $ignoreMissing): array
	{
		$dir = new RecursiveDirectoryIterator(
			DIR_SCHEMAS,
			RecursiveDirectoryIterator::SKIP_DOTS
		);

		$refs = [
			'clear' => [],
			'delete' => [],
			'fk' => [],
			'missing' => []
		];

		foreach (new RecursiveIteratorIterator($dir) as $o)
		{
			/** @var \SplFileInfo $o */
			$path = $o->getPathname();
			$pathRel = self::absolutePathToRelative($path, DIR_SCHEMAS);
			$schema = require $path;

			foreach ([
				'clear',
				'delete',
				'fk'
			] as $type)
			{
				if (isset($schema['$refs'][$type]))
				{
					$name = substr($pathRel, 0, -4); // rm ext

					$refs[$type][$name] = $schema['$refs'][$type];
				}
			}
		}

		// detect missing $refs.clear / $refs.delete constraints
		$refsClear = &$refs['clear'];
		$refsDel = &$refs['delete'];
		foreach ($refs['fk'] as $lColl => $schema)
		{
			foreach ($schema as $fColl => $fields)
			{
				foreach ($fields as $lf => $ff)
				{
					if (in_array($fColl, $ignoreMissing))
					{
						continue; // ignore
					}

					// strip nullable$ prefix
					$lf = RefFk::stripNullablePrefix($lf);

					if (
						// clear
						(
							// check foreign collection exists
							!isset($refsClear[$fColl])
							||
							// check foreign collection => local collection exists
							!isset($refsClear[$fColl][$lColl])
							||
							// foreign collection => local collection (must be array)
							!is_array($refsClear[$fColl][$lColl])
							||
							// check if local field exists as constraint
							!in_array($lf, $refsClear[$fColl][$lColl])
						)
						&&
						// delete
						(
							// check foreign collection exists
							!isset($refsDel[$fColl])
							||
							// check foreign collection => local collection exists
							!isset($refsDel[$fColl][$lColl])
							||
							// foreign collection => local collection (must be array)
							!is_array($refsDel[$fColl][$lColl])
							||
							// check if local field exists as constraint
							!in_array($lf, $refsDel[$fColl][$lColl])
						)
					)
					{
						$refs['missing'][$fColl][$lColl][] = $lf;
						ksort($refs['missing'][$fColl]);
					}
				}
			}
		}

		ksort($refs['delete']);
		ksort($refs['fk']);
		ksort($refs['missing']);

		return $refs;
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
