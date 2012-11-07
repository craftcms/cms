<?php
namespace Blocks;

/**
 *
 */
class MigrateCommand extends \MigrateCommand
{
	/**
	 *
	 */
	public function init()
	{
		$this->migrationTable = 'migrations';
	}

	/**
	 * @return string
	 */
	public function getHelp()
	{
		return <<<EOD
USAGE
  yiic migrate [action] [parameter]

DESCRIPTION
  This command provides support for database migrations. The optional
  'action' parameter specifies which specific migration task to perform.
  It can take these values: up, to, create, history, new, mark.
  If the 'action' parameter is not given, it defaults to 'up'.
  Each action takes different parameters. Their usage can be found in
  the following examples.

EXAMPLES
 * yiic migrate
   Applies ALL new migrations. This is equivalent to 'yiic migrate up'.

 * yiic migrate create create_user_table
   Creates a new migration named 'create_user_table'.

 * yiic migrate up 3
   Applies the next 3 new migrations.

 * yiic migrate to 101129_185401
   Migrates up or down to version 101129_185401.

 * yiic migrate mark 101129_185401
   Modifies the migration history up or down to version 101129_185401.
   No actual migration will be performed.

 * yiic migrate history
   Shows all previously applied migration information.

 * yiic migrate history 10
   Shows the last 10 applied migrations.

 * yiic migrate new
   Shows all new migrations.

 * yiic migrate new 10
   Shows the next 10 migrations that have not been applied.

EOD;
	}

	/**
	 * @param $class
	 * @return bool|void
	 */
	protected function migrateDown($class)
	{
		die("Down migrations are not supported\n");
	}

	/**
	 * @param $args
	 * @return mixed
	 */
	public function actionTo($args)
	{
		if (isset($args[0]))
		{
			$version = $args[0];
		}
		else
		{
			$this->usageError('Please specify which version to migrate to.');
		}

		$originalVersion = $version;
		if (preg_match('/^m?(\d{6}_\d{6})(_.*?)?$/', $version, $matches))
		{
			$version = 'm'.$matches[1];
		}
		else
		{
			die("Error: The version option must be either a timestamp (e.g. 101129_185401)\nor the full name of a migration (e.g. m101129_185401_create_user_table).\n");
		}

		// try migrate up
		$migrations = $this->getNewMigrations();
		foreach ($migrations as $i => $migration)
		{
			if (strpos($migration, $version.'_') === 0)
			{
				$this->actionUp(array($i + 1));
				return;
			}
		}

		die("Error: Unable to find the version '$originalVersion'.\n");
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
	 * @param $limit
	 * @return mixed
	 */
	protected function getMigrationHistory($limit)
	{
		$db = $this->getDbConnection();
		if ($db->schema->getTable('{{'.$this->migrationTable.'}}') === null)
		{
			$this->createMigrationHistoryTable();
		}

		return \CHtml::listData($db->createCommand()
			->select('version, apply_time')
			->from($this->migrationTable)
			->order('version DESC')
			->limit($limit)
			->queryAll(), 'version', 'apply_time');
	}

	/**
	 * @param $class
	 * @return mixed
	 */
	protected function instantiateMigration($class)
	{
		$file = IOHelper::normalizePathSeparators($this->migrationPath.'/'.$class.'.php');

		require_once($file);

		$class = __NAMESPACE__.'\\'.$class;
		$migration = new $class;
		$migration->setDbConnection($this->getDbConnection());

		return $migration;
	}

	/**
	 *
	 */
	protected function createMigrationHistoryTable()
	{
		$db = $this->getDbConnection();
		echo 'Creating migration history table "'.$this->migrationTable.'"...';
		$db->createCommand()->createTable($this->migrationTable, array(
			'version' => 'string NOT NULL',
			'apply_time' => 'integer',
		));

		$db->createCommand()->insert($this->migrationTable, array(
			'version' => static::BASE_MIGRATION,
			'apply_time' => time(),
		));

		echo "done.\n";
	}

	/**
	 * @return string
	 */
	protected function getTemplate()
	{
		if($this->templateFile !== null)
		{
			return IOHelper::getFileContents(Blocks::getPathOfAlias($this->templateFile).'.php');
		}
		else
		{
			return <<<EOD
<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class {ClassName} extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		return true;
	}
}

EOD;
		}
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
			$path = IOHelper::getFolderName($params[0][0]);
		}
		else
		{
			$path = Blocks::getPathOfAlias($this->migrationPath);
		}

		if ($path === false || !IOHelper::folderExists($path))
		{
			echo 'Error: The migration directory does not exist: '.$this->migrationPath."\n";
			exit(1);
		}

		$this->migrationPath = $path;

		$yiiVersion = Blocks::getYiiVersion();
		echo "\nBlocks Migration Tool v1.0 (based on Yii v{$yiiVersion})\n\n";

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
	 * @return int
	 */
	public function actionCreate($args)
	{
		if (isset($args[0]))
		{
			$name = IOHelper::getFileName($args[0], false);
		}
		else
		{
			$this->usageError('Please provide the name of the new migration.');
			return 1;
		}

		if (!preg_match('/^\w+$/', $name))
		{
			echo "Error: The name of the migration must contain letters, digits and/or underscore characters only.\n";
			return 1;
		}

		$name = 'm'.gmdate('ymd_His').'_'.$name;
		$content = strtr($this->getTemplate(), array('{ClassName}' => $name));
		$file = $this->migrationPath.DIRECTORY_SEPARATOR.$name.'.php';

		if ($this->confirm("Create new migration '$file'?"))
		{
			IOHelper::writeToFile($file, $content);
			echo "New migration created successfully.\n";
		}
	}
}
