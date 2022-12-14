<?php
/**
 * Lark Framework
 *
 * @copyright Shay Anderson <https://shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/lark-framework/blob/master/LICENSE.md>
 * @link <https://github.com/shayanderson/lark-framework>
*/
declare(strict_types=1);

namespace Lark\Cli;

use Lark\Cli\Console\Command;
use Lark\Cli\Console\ConsoleException;

/**
 * Console
 *
 * @author Shay Anderson
 */
class Console extends \Lark\Cli
{
	/**
	 * Init
	 *
	 * @return void
	 */
	protected function __init(): void
	{
		///////////////////////////////////////////////////////////////////////////////////////
		// init project
		$this->option('--init', 'Initialize project', function ()
		{
			Command::init();
		});

		///////////////////////////////////////////////////////////////////////////////////////
		// commit command
		$this->command('commit', 'Commit revisions', ['c'])
			->action([Command::class, 'commit']);

		///////////////////////////////////////////////////////////////////////////////////////
		// help command
		$this->command('help', 'Display help')
			->arg('command', 'Command name', ['optional'])
			->action(function ($command = null)
			{
				$command ? $this->helpCommand($command) : $this->help();
			});

		///////////////////////////////////////////////////////////////////////////////////////
		// model command
		$this->command('model', 'Create a model', ['m'])
			->arg(
				'name',
				'Model name like "User" or "Api/User" (do not use class name like "Api\User")'
			)
			->arg('schema-name', 'Schema name like "users" or "api/users"', ['optional'])
			->option('--dbs', 'Database string like "default$app$users"')
			->option('--schema', 'Link to existing schema like "users.php" or "users"')
			->option(
				'-t, --template',
				'Path to model template file for using custom template (-t=PATH)'
			)
			->option('-y', 'Automatic defaults for prompts')
			->action([Command::class, 'model']);

		///////////////////////////////////////////////////////////////////////////////////////
		// revision command
		$this->command('rev', 'Create a revision')
			->arg(
				'name',
				'Model name like "User" or "Api/User" (do not use class name like "Api\User")'
					. ' or database string "[connectionId]$[database]$[collection]"'
			)
			->arg('description', 'Description used for revision file name')
			->option('-c, --create', 'Use create collection template')
			->action([Command::class, 'rev']);

		///////////////////////////////////////////////////////////////////////////////////////
		// remove revision command
		$this->command('rmrev', 'Remove a revision')
			->arg('revision-id', 'Revision ID like \'20220000000000000$default$app$sessions$create\''
				. ', use single quotes around ID or escape dollar signs using "\$"')
			->action([Command::class, 'rmrev']);

		///////////////////////////////////////////////////////////////////////////////////////
		// route command
		$this->command('route', 'Create a route', ['r'])
			->arg('name', 'Route name like "users" or "api/users"')
			->arg(
				'model-name',
				'Specific model name like "User" or "Api/User"'
					. ' (do not use class name like "Api\User")',
				['optional']
			)
			->option('--dbs', 'Database string like "default$app$users"')
			->option(
				'--model',
				'Link to existing model like "User" or "Api/User"'
					. ' (do not use class name like "Api\User")'
			)
			->option('--schema', 'Link to existing schema like "users.php" or "users"')
			->option(
				'-t, --template',
				'Path to route template file for using custom template (-t=PATH)'
			)
			->option(
				'--templatemodel',
				'Path to model template file for using custom template'
			)
			->option('-y', 'Automatic defaults for prompts')
			->action([Command::class, 'route']);

		///////////////////////////////////////////////////////////////////////////////////////
		// schema command
		$this->command('schema', 'Create a schema', ['s'])
			->arg('name', 'Schema name like "users" or "api/users"', ['optional'])
			->option(
				'-c, --compile',
				'Compile schema from schema template, compiles all when no name arg'
			)
			->option(
				'--ignore',
				'Use with --refs option to ignore missing clear/delete refs,'
					. ' like --ignore=users,users2'
			)
			->option('--missing', 'Use with --refs option to only display missing clear/delete refs')
			->option(
				'--refs',
				'Display existing and possible missing clear/delete refs for all compiled schemas'
			)
			->action([Command::class, 'schema']);
	}

	/**
	 * Output error and exit
	 *
	 * @param string $text
	 * @return void
	 */
	public function error(string $text): void
	{
		$this->output()->error($text);
		$this->exit(1);
	}

	/**
	 * Directory getter
	 *
	 * @param string $name
	 * @return string
	 */
	public static function getDir(string $name): string
	{
		$dirs = self::getDirs();

		if (!isset($dirs[$name]))
		{
			throw new ConsoleException('Unknown directory name "' . $name . '"');
		}

		if (!is_dir($dirs[$name]))
		{
			throw new ConsoleException(
				'Directory for "' . $name . '" not found ("' . $dirs[$name] . '")'
			);
		}

		return $dirs[$name];
	}

	/**
	 * Directories getter
	 *
	 * @return array
	 */
	public static function getDirs(): array
	{
		return [
			'model' => DIR_MODELS,
			'revision' => DIR_REVISIONS,
			'route' => DIR_ROUTES,
			'schema' => DIR_SCHEMAS,
			'template' => DIR_TEMPLATES
		];
	}
}
