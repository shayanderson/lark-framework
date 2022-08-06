<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Router;

use Lark\Router;

/**
 * Router controller interface
 *
 * @author Shay Anderson
 */
interface RouteControllerInterface
{
	/**
	 * Bind controller routes
	 *
	 * @param Router $router
	 * @return void
	 */
	public function bind(Router $router): void;
}
