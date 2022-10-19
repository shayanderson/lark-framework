<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Debugger;

/**
 * Debugger info dumper
 *
 * @author Shay Anderson
 */
class Dumper
{
	/**
	 * Group
	 *
	 * @var array
	 */
	private static array $group = [];

	/**
	 * Last group name
	 *
	 * @var string
	 */
	private static string $groupLast = '';

	/**
	 * Current number
	 *
	 * @var integer
	 */
	private static int $num = 0;

	/**
	 * Dump final
	 *
	 * @return void
	 */
	private static function dumpFinal(): void
	{
		if (self::$group)
		{
			Template::group(self::$group);
		}
	}

	/**
	 * Dump info object
	 *
	 * @param Info $info
	 * @return void
	 */
	private static function dumpInfo(Info &$info): void
	{
		self::$num--;
		$info->id(self::$num + 1);

		// dump group
		if (self::$group && !$info->hasGroup())
		{
			Template::group(self::$group);
			self::$group = [];
			self::$groupLast = '';
		}

		// dump different group
		if (self::$group && $info->hasGroup() && $info->getGroup() !== self::$groupLast)
		{
			Template::group(self::$group);
			self::$group = [];
		}

		// add to pending group
		if ($info->hasGroup())
		{
			self::$group[] = $info;
			self::$groupLast = $info->getGroup();
			return;
		}

		// dump
		Template::info($info);
	}

	/**
	 * Dump all
	 *
	 * @param array $info
	 * @return void
	 */
	public static function dump(array $info): void
	{
		$count = count($info);
		self::$num = $count;

		foreach ($info as $o)
		{
			self::dumpInfo($o);
		}

		self::dumpFinal();
		Template::footer(self::class, $count);
	}
}
