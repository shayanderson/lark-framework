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
use Lark\Cli\Console;
use Lark\Cli\Output;
use Lark\Database\Connection;

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
				throw new CliException(ucfirst($dirName) . ' directory not found', [
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
	 * Create model
	 *
	 * @param string $inputName
	 * @param string|null $inputNameSchema
	 * @param string|null $optTemplatePath
	 * @param boolean|null $optUseDefaults
	 * @param SchemaFile|null $schemaFile
	 * @return string
	 */
	public function model(
		string $inputName,
		?string $inputNameSchema,
		?string $optTemplatePath = null,
		?bool $optUseDefaults = null,
		?SchemaFile $schemaFile = null
	): string
	{
		$inputName = new InputName($inputName);
		$className = ModelFile::classNameFromInputName($inputName);

		///////////////////////////////////////////////////////////////////////////////////////
		// schema
		if ($inputNameSchema)
		{
			$schemaFile = $this->schema($inputNameSchema);
		}

		$schamaPath = null;
		if ($schemaFile)
		{
			// make relative, rm "app/" dir
			$schamaPath = '/' . substr($schemaFile->pathRelative(), 4);

			$inputNameSchema = $schemaFile->getInputName();
		}

		///////////////////////////////////////////////////////////////////////////////////////
		// database string
		$dbString = null;
		if ($optUseDefaults || $this->console->confirm('Use database in model?', true))
		{
			$defaultConnId = Connection::getDefaultConnectionId();

			if (!$defaultConnId)
			{
				throw new CliException('No database connections found');
			}

			$dbString = $defaultConnId . '$app$'
				. ($inputNameSchema
					? $inputNameSchema->getName()
					: strtolower($inputName->getName()));

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
		$modelFile = new ModelFile($inputName, $inputName->getName() . '.php');

		if ($modelFile->write(
			Template::model(
				$optTemplatePath,
				$inputName->getName(),
				ModelFile::namespaceFromInputName($inputName),
				$schamaPath,
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
	 * Create route
	 *
	 * @param string $inputName
	 * @param string|null $inputNameModel
	 * @param string|null $optTemplatePath
	 * @param string|null $optTemplateModelPath
	 * @param boolean|null $optUseDefaults
	 * @return void
	 */
	public function route(
		string $inputName,
		?string $inputNameModel,
		?string $optTemplatePath = null,
		?string $optTemplateModelPath = null,
		?bool $optUseDefaults = null
	): void
	{
		$inputNameStr = $inputName;
		$inputName = new InputName($inputName);

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
		if ($optUseDefaults || $this->console->confirm('Create schema?', true))
		{
			$schemaFile = $this->schema($inputNameStr);
		}
		else
		{
			$this->output()->echo();
		}

		///////////////////////////////////////////////////////////////////////////////////////
		// model
		$modelClassName = null;
		if ($optUseDefaults || $this->console->confirm('Create model?', true))
		{
			if (!$inputNameModel)
			{
				$inputNameModel = ModelFile::inputNameToClassName($inputName);
			}

			$modelClassName = $this->model(
				$inputNameModel,
				null,
				$optTemplateModelPath,
				$optUseDefaults,
				$schemaFile
			);
		}
		else
		{
			$this->output()->echo();
		}

		///////////////////////////////////////////////////////////////////////////////////////
		// route file
		$routeFile = new RouteFile($inputName, $inputName->getName() . '.php');

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
