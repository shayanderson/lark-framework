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
 * Console route file
 *
 * @author Shay Anderson
 */
class RouteFile extends File
{
	/**
	 * @inheritDoc
	 */
	const TYPE = 'route';

	/**
	 * Route loader getter
	 *
	 * @return array
	 */
	private static function &getRouteLoader(): array
	{
		$loaderPath = self::getRouteLoaderPath();

		$load = [];

		if (file_exists($loaderPath))
		{
			$load = include $loaderPath;
		}

		if (!is_array($load))
		{
			$load = [];
		}

		return $load;
	}

	/**
	 * Route loader path getter
	 *
	 * @return string
	 */
	public static function getRouteLoaderPath(): string
	{
		return DIR_APP . '/routes/_load.php';
	}

	/**
	 * Check if base route exists in loader, if so throw exception
	 *
	 * @param string $baseRoute
	 * @return void
	 */
	public static function requireRouteNotInLoader(string $baseRoute): void
	{
		if (isset(self::getRouteLoader()[$baseRoute]))
		{
			throw new ConsoleException('Base route "' . $baseRoute . '" already exists in routes'
				. ' load file and cannot be appended ("' . self::getRouteLoaderPath() . '")');
		}
	}

	/**
	 * Push route into route loader
	 *
	 * @param string $baseRoute
	 * @param string $path
	 * @return boolean
	 */
	public static function pushRoutesLoader(string $baseRoute, string $path): bool
	{
		self::requireRouteNotInLoader($baseRoute);
		$load = &self::getRouteLoader();

		// add route
		$load[$baseRoute] = $path;

		// write
		return self::writeRouteLoader($load);
	}

	/**
	 * Write route loader
	 *
	 * @param array $load
	 * @return boolean
	 */
	private static function writeRouteLoader(array $load): bool
	{
		ksort($load);

		$loadStr = var_export($load, true);
		$out = <<<"OUT"
<?php
return {$loadStr};
OUT;
		$f = new \Lark\File(self::getRouteLoaderPath());
		return $f->write($out);
	}
}
