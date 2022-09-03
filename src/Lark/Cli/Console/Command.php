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

use Lark\Cli;
use Lark\Cli\Console;
use Lark\Cli\Output;
use Lark\Database\Connection;
use Lark\File;

/**
 * Console commands
 *
 * @author Shay Anderson
 */
class Command
{
	/**
	 * Console
	 *
	 * @var Console
	 */
	private Console $console;

	/**
	 * Init
	 */
	public function __construct()
	{
		$this->console = Console::getInstance();

		foreach (Console::getDirs() as $dirName => $dir)
		{
			if (!is_dir($dir))
			{
				throw new ConsoleException(ucfirst($dirName) . ' directory not found', [
					'directory' => $dir
				]);
			}
		}
	}

	/**
	 * Commit all revisions
	 *
	 * @return void
	 */
	public function commit(): void
	{
		$this->output()->echo('Running commits for all revisions...');
		Revision::run($this->console, Console::getDir('revision'));
	}

	/**
	 * Initialize project
	 *
	 * @return void
	 */
	public static function init(): void
	{
		$cli = Cli::getInstance();
		$out = Console::getInstance()->output();
		$out->echo();
		$out->echo('Initializing Lark project...');
		$out->echo();

		// rm extra files
		foreach ([
			'./CHANGELOG.md',
			'./LICENSE.md',
			'./README.md'
		] as $fp)
		{
			$file = new File($fp);

			if (
				$file->exists()
				&& $cli->confirm('Remove file "' . $file->path() . '"?')
				&& $file->delete()
			)
			{
				$out->ok('  Removed file "' . $file->path() . '"');
			}
		}

		// create empty project dirs
		foreach ([
			'./app/Middleware',
			'./app/Model',
			'./app/schemas',
			'./revisions'
		] as $dir)
		{
			if (!is_dir($dir) && mkdir($dir))
			{
				$out->ok('Created project directory "' . $dir . '"');
			}
		}

		$out->echo();

		// exit
		Cli::getInstance()->exit();
	}

	/**
	 * Create model
	 *
	 * @param string $inputName
	 * @param string|null $inputNameSchema
	 * @param string|null $optDbs
	 * @param string|null $optSchemaPath
	 * @param string|null $optTemplatePath
	 * @param boolean|null $optUseDefaults
	 * @param SchemaFile|null $schemaFile
	 * @return string
	 */
	public function model(
		string $inputName,
		?string $inputNameSchema,
		?string $optDbs = null,
		?string $optSchemaPath = null,
		?string $optTemplatePath = null,
		?bool $optUseDefaults = null,
		?SchemaFile $schemaFile = null
	): string
	{
		$inputName = new InputName($inputName);
		$className = ModelFile::classNameFromInputName($inputName);
		$modelFile = new ModelFile($inputName, $inputName->getName() . '.php');
		$modelFile->fileExistsAbort();

		///////////////////////////////////////////////////////////////////////////////////////
		// schema
		if ($inputNameSchema && $optSchemaPath)
		{
			throw new ConsoleException(
				'Cannot use argument "schema-name" and option "schema" together'
			);
		}

		if ($inputNameSchema)
		{
			$schemaFile = $this->schema($inputNameSchema);
		}

		$schemaPath = null;
		if ($schemaFile)
		{
			$schemaPath = $schemaFile->pathRelativeSchemaDir();
			$inputNameSchema = $schemaFile->getInputName();
		}

		if ($optSchemaPath)
		{
			$schemaPath = SchemaFile::formatPathName($optSchemaPath);
		}

		///////////////////////////////////////////////////////////////////////////////////////
		// database string
		$dbString = $optDbs;
		if (
			!$optDbs
			&& ($optUseDefaults || $this->console->confirm('Use database in model?', true))
		)
		{
			$defaultConnId = Connection::getDefaultConnectionId();

			if (!$defaultConnId)
			{
				throw new ConsoleException('No database connections found');
			}

			$dbString = $defaultConnId . '$app$';

			if ($inputNameSchema)
			{
				$dbString .= $inputNameSchema->getName();
			}
			else
			{
				$dbString .= strtolower($inputName->getName());
				if (substr($dbString, -1) !== 's')
				{
					$dbString .= 's'; // auto append 's'
				}
			}

			if (!$optUseDefaults)
			{
				$dbString = $this->console->input('Enter database string:', $dbString);
			}

			// check for valid database string
			Connection::parseDatabaseString($dbString);

			$this->output()->dim('Database string: "' . $dbString . '"');
			$this->output()->echo();
		}

		///////////////////////////////////////////////////////////////////////////////////////
		// model
		$this->output()->echo('Model class: "' . $className . '"');

		if ($modelFile->write(
			Template::model(
				$optTemplatePath,
				$inputName->getName(),
				ModelFile::namespaceFromInputName($inputName),
				$schemaPath,
				$dbString
			)
		))
		{
			$this->output()->ok(
				'Model file for "' . $className . '" created',
				''
			);
			$this->output()->dim(' ("' . $modelFile->path() . '")');
		}

		$this->output()->echo();

		return $className;
	}

	/**
	 * Output object getter
	 *
	 * @return Output
	 */
	private function output(): Output
	{
		return $this->console->output();
	}

	/**
	 * Revision command
	 *
	 * @param string $name
	 * @param string $description
	 * @param mixed $optCreate
	 * @return void
	 */
	public function rev(string $name, string $description, $optCreate): void
	{
		$file = new RevisionFile($name, $description);
		$file->create($this->console, $optCreate === true);
	}

	/**
	 * Remove revision command
	 *
	 * @param string $revId
	 * @return void
	 */
	public function rmrev(string $revId): void
	{
		if (!$this->console->confirm('Remove revision "' . $revId . '"?'))
		{
			$this->console->abort();
		}

		if (RevisionFile::remove($revId))
		{
			$this->output()->ok('Removed revision file for "' . $revId . '"');
		}
		else
		{
			$this->output()->error('Failed to remove revision file for "' . $revId . '"');
		}

		$rev = RevisionFile::fromRevIdToObject($revId);

		$revModel = new RevisionModel(
			$rev->getConnectionId(),
			$rev->getDatabase(),
			$rev->getCollection(),
			$revId
		);

		if ($revModel->delete() > 0)
		{
			$this->output()->ok('Removed revision from database "' . $revId . '"');
		}
		else
		{
			$this->output()->warn('Failed to remove revision from database "' . $revId . '"');
		}
	}

	/**
	 * Create route
	 *
	 * @param string $inputName
	 * @param string|null $inputNameModel
	 * @param string|null $optDbs
	 * @param string|null $optModel
	 * @param string|null $optSchemaPath
	 * @param string|null $optTemplatePath
	 * @param string|null $optTemplateModelPath
	 * @param boolean|null $optUseDefaults
	 * @return void
	 */
	public function route(
		string $inputName,
		?string $inputNameModel,
		?string $optDbs = null,
		?string $optModel = null,
		?string $optSchemaPath = null,
		?string $optTemplatePath = null,
		?string $optTemplateModelPath = null,
		?bool $optUseDefaults = null
	): void
	{
		$inputNameStr = $inputName;
		$inputName = new InputName($inputName);
		$routeFile = new RouteFile($inputName, $inputName->getName() . '.php');
		$routeFile->fileExistsAbort();

		$this->output()->echo('Route name: "' . $inputName->getNamespaceName() . '"');
		$this->output()->echo();

		///////////////////////////////////////////////////////////////////////////////////////
		// base route
		$baseRoute = '/' . $inputName->getNamespaceName();

		if (!$optUseDefaults)
		{
			$baseRoute = $this->console->input('Enter base route:', $baseRoute);
		}

		$this->output()->dim('Base route: "' . $baseRoute . '"');
		$this->output()->echo();

		// ensure base route does not exist in route loader file
		RouteFile::requireRouteNotInLoader($baseRoute);

		///////////////////////////////////////////////////////////////////////////////////////
		// schema
		$schemaFile = null;
		if (!$optSchemaPath && ($optUseDefaults || $this->console->confirm('Create schema?', true)))
		{
			$schemaFile = $this->schema($inputNameStr);
		}

		///////////////////////////////////////////////////////////////////////////////////////
		// model
		$modelClassName = null;
		if ($optModel)
		{
			$modelClassName = ModelFile::classNameFromInputName(new InputName($optModel));
		}
		else if ($optUseDefaults || $this->console->confirm('Create model?', true))
		{
			if (!$inputNameModel)
			{
				$inputNameModel = ModelFile::inputNameToClassName($inputName);
			}

			$modelClassName = $this->model(
				$inputNameModel,
				null,
				$optDbs,
				$optSchemaPath,
				$optTemplateModelPath,
				$optUseDefaults,
				$schemaFile
			);
		}

		///////////////////////////////////////////////////////////////////////////////////////
		// route file
		if ($routeFile->write(
			Template::route(
				$optTemplatePath,
				$baseRoute,
				$modelClassName
			)
		))
		{
			$this->output()->ok(
				'Route file for "' . $baseRoute . '" created',
				''
			);
			$this->output()->dim(' ("' . $routeFile->path() . '")');

			if (RouteFile::pushRoutesLoader($baseRoute, $inputName->getNamespaceName()))
			{
				$this->output()->ok('Base route "' . $baseRoute . '" appended to route loader', '');
				$this->output()->dim(' ("' . RouteFile::getRouteLoaderPath() . '")');
			}
			else
			{
				$this->console->error(
					'Failed to append base route "' . $baseRoute . '" to route loader',
					[
						'routeLoaderPath' => RouteFile::getRouteLoaderPath()
					]
				);
			}
		}

		$this->output()->echo();
	}

	/**
	 * Create schema
	 *
	 * @param string|InputName $inputName
	 * @param bool|null $optCompile
	 * @return SchemaFile|null
	 */
	public function schema($inputName, ?bool $optCompile = null): ?SchemaFile
	{
		// compile all
		if (!$inputName)
		{
			if (!$optCompile)
			{
				$this->console->error('Compile all command requries -c option or --compile option');
			}

			$this->output()->echo('Compiling all schema templates...');
			SchemaTemplateFile::compileAll();

			$this->output()->echo();

			return null;
		}

		if (!$inputName instanceof InputName)
		{
			$inputName = new InputName($inputName);
		}

		$this->output()->echo('Schema name: "' . $inputName->getNamespaceName() . '"');

		$schemaFile = SchemaFile::factory($inputName);
		$schemaTemplateFile = SchemaTemplateFile::factory($inputName);

		// compile
		if ($optCompile)
		{
			$schemaTemplateFile->compile();
			return $schemaFile;
		}

		// do not overwrite existing schema file
		if ($schemaFile->exists())
		{
			$this->console->error('Schema file already exists for "'
				. $inputName->getNamespaceName() . '" ("' . $schemaFile->path() . '")');
		}

		// create template
		$tpl = [
			'id' => ['string', 'id']
		];

		// write template
		$schemaTemplateFile->write($tpl);

		$this->output()->ok(
			'Schema template for "' . $inputName->getNamespaceName() . '" created',
			''
		);
		$this->output()->dim(' ("' . $schemaTemplateFile->path() . '")');

		// compile schema from template
		$schemaTemplateFile->compile();

		$this->output()->echo();

		return $schemaFile;
	}
}
