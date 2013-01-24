<?php
namespace Blocks;

/**
 *
 */
class MigrateCommand extends \MigrateCommand
{
	/**
	 * @var string The path of the template file for generating new migrations.
	 */
	public $templateFile = 'app.components.updates.migrationtemplate';

	/**
	 * @access private
	 * @var string The real path to the migrations folder.
	 */
	private $_rootMigrationPath;

	/**
	 * Init
	 */
	public function init()
	{
		// Set migrationsTable to whatever it is in the MigrationsService
		$this->migrationTable = blx()->migrations->migrationTable;
	}

	/**
	 * @param string $action
	 * @param array  $params
	 * @return bool
	 */
	public function beforeAction($action, $params)
	{
		if ($action == 'create')
		{
			$path = $params[0][0];
		}
		else
		{
			$path = Blocks::getPathOfAlias($this->migrationPath);
		}

		if ($path === false || !IOHelper::folderExists($path))
		{
			echo 'The migration folder does not exist: '.$path."\n";

			if ($action == 'create')
			{
				echo 'Creating '.$path."\n";

				if (!IOHelper::createFolder($path))
				{
					echo 'Sorry... I tried to create the folder, but could not.';
					exit(1);
				}
			}
			else
			{
				exit(1);
			}
		}

		$this->_rootMigrationPath = $path;

		$yiiVersion = Blocks::getYiiVersion();
		echo "\nBlocks Migration Tool (based on Yii v{$yiiVersion})\n\n";

		if ($action == 'create')
		{
			return true;
		}
		else
		{
			return parent::beforeAction($action, $params);
		}
	}

	/**
	 * @param $args
	 * @return void
	 */
	public function actionDown($args)
	{
		die("Down migrations are not supported\n");
	}

	/**
	 * @param $args
	 * @return int
	 */
	public function actionCreate($args)
	{
		if (isset($args[1]))
		{
			$name = $args[1];

			if (!preg_match('/^\w+$/', $name))
			{
				echo "Error: The name of the migration must contain letters, digits and/or underscore characters only.\n";
				return 1;
			}

			$fullName = 'm'.gmdate('ymd_His').'_'.$name;
			$migrationNameDesc = 'mYYMMDD_HHMMSS_migrationName';
		}
		else
		{
			$this->usageError('Please provide a name for the new migration.');
			return 1;
		}

		if (isset($args[2]))
		{
			$pluginHandle = $args[2];

			if (!preg_match('/^\w+$/', $pluginHandle))
			{
				echo "Error: The name of the plugin must contain letters, digits and/or underscore characters only.\n";
				return 1;
			}

			$fullName = 'm'.gmdate('ymd_His').'_'.strtolower($pluginHandle).'_'.$name;
			$migrationNameDesc = 'mYYMMDD_HHMMSS_pluginHandle_migrationName';
		}

		$content = strtr($this->getTemplate(), array('{ClassName}' => $fullName, '{MigrationNameDesc}' => $migrationNameDesc));
		$file = $this->_rootMigrationPath.DIRECTORY_SEPARATOR.$fullName.'.php';

		if ($this->confirm("Create new migration '$file'?"))
		{
			IOHelper::writeToFile($file, $content);
			echo "New migration created successfully.\n";
		}
	}

	/**
	 * @param $class
	 * @return bool
	 */
	protected function migrateUp($class)
	{
		return blx()->migrations->migrateUp($class);
	}

	/**
	 * @param $class
	 * @return mixed
	 */
	protected function instantiateMigration($class)
	{
		$file = IOHelper::normalizePathSeparators($this->_rootMigrationPath.'/'.$class.'.php');

		require_once($file);

		$class = __NAMESPACE__.'\\'.$class;
		$migration = new $class;
		$migration->setDbConnection($this->getDbConnection());

		return $migration;
	}

	/**
	 * @param $limit
	 * @return mixed
	 */
	protected function getMigrationHistory($limit)
	{
		$migrations = blx()->migrations->getMigrationHistory($limit);

		$column = 'applyTime';

		if (isset($migrations[0]))
		{
			if (isset($migrations[0]['apply_time']))
			{
				$column = 'apply_time';
			}
		}

		// Convert the dates to Unix timestamps
		foreach ($migrations as &$migration)
		{
			$migration[$column] = $migration[$column]->getTimestamp();
		}

		return HtmlHelper::listData($migrations, 'version', $column);
	}

	/**
	 * @return array
	 */
	protected function getNewMigrations()
	{
		return blx()->migrations->getNewMigrations();
	}
}
