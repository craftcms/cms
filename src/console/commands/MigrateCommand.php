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
			$path = IOHelper::getFolderName($params[0][0]);
		}
		else
		{
			$path = Blocks::getPathOfAlias($this->migrationPath);
		}

		if ($path === false || !IOHelper::folderExists($path))
		{
			echo 'Error: The migration directory does not exist: '.$this->_rootMigrationPath."\n";
			exit(1);
		}

		$this->_rootMigrationPath = $path;

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
		$file = $this->_rootMigrationPath.DIRECTORY_SEPARATOR.$name.'.php';

		if ($this->confirm("Create new migration '$file'?"))
		{
			IOHelper::writeToFile($file, $content);
			echo "New migration created successfully.\n";
		}
	}

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

		// Convert the dates to Unix timestamps
		foreach ($migrations as &$migration)
		{
			$migration['apply_time'] = $migration['apply_time']->getTimestamp();
		}

		return HtmlHelper::listData($migrations, 'version', 'apply_time');
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

	protected function getNewMigrations()
	{
		return blx()->migrations->getNewMigrations();
	}
}
