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

/**
 * Console model file
 *
 * @author Shay Anderson
 */
class ModelFile extends File
{
	/**
	 * @inheritDoc
	 */
	const TYPE = 'model';

	/**
	 * Class name from input name object getter
	 *
	 * @param InputName $inputName
	 * @return string
	 */
	public static function classNameFromInputName(InputName $inputName): string
	{
		return self::namespaceFromInputName($inputName) . '\\' . $inputName->getName();
	}

	/**
	 * Input name object to class name getter
	 *
	 * @param InputName $inputName
	 * @return string
	 */
	public static function inputNameToClassName(InputName $inputName): string
	{
		$parts = $inputName->getNamespace();
		$parts[] = $inputName->getName();

		$parts = array_map(function ($value)
		{
			// split on any non-alphanumeric
			$values = preg_split('/[^a-zA-Z0-9]/', $value);
			// ucfirst words
			$values = array_map('ucfirst', $values);
			// drop plural 's' (not exact)
			$values = array_map(function ($v)
			{
				return rtrim($v, 's');
			}, $values);
			return implode('', $values);
		}, $parts);

		return implode('/', $parts);
	}

	/**
	 * Namespace from input name object getter
	 *
	 * @param InputName $inputName
	 * @return string
	 */
	public static function namespaceFromInputName(InputName $inputName): string
	{
		return 'App\Model'
			. ($inputName->hasNamespace()
				? '\\' . implode('\\', $inputName->getNamespace())
				: null
			);
	}
}
