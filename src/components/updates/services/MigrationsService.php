<?php
namespace Blocks;

/**
 *
 */
class MigrationsService extends BaseApplicationComponent
{
	private $_db;

	/**
	 * @var string the folder that stores the migrations. This must be specified
	 * in terms of a path alias, and the corresponding directory must exist.
	 * Defaults to 'application.migrations' (meaning 'protected/migrations').
	 */
	public $migrationPath = 'application.migrations';

	/**
	 * @var string the application component ID that specifies the database connection for
	 * storing migration information. Defaults to 'db'.
	 */
	public $connectionID = 'db';

	/**
	 * @var string the default command action. It defaults to 'up'.
	 */
	public $defaultAction = 'up';

	/**
	 * @var
	 */
	public $migrationTable;

	/**
	 * @throws Exception
	 * @return bool|void
	 */
	public function init()
	{
		$path = Blocks::getPathOfAlias($this->migrationPath);

		if ($path === false || !IOHelper::folderExists($path))
		{
			throw new Exception(Blocks::t('Error: The migration folder “{folder}” doesn’t exist.', array('folder' => $this->migrationPath)));
		}

		$this->migrationPath = $path;

		$migration = new MigrationRecord('install');
		$this->migrationTable = $migration->getTableName();
	}

	/**
	 * @return mixed
	 */
	public function runToTop()
	{
		if (($migrations = $this->getNewMigrations()) === array())
		{
			Blocks::log('No new migration(s) found. Your system is up-to-date.', \CLogger::LEVEL_INFO);
			return true;
		}

		$total = count($migrations);

		Blocks::log("Total $total new ".($total === 1 ? 'migration':'migrations')." to be applied:".PHP_EOL, \CLogger::LEVEL_INFO);

		foreach ($migrations as $migration)
		{
			Blocks::log($migration.PHP_EOL, \CLogger::LEVEL_INFO);
		}

		foreach ($migrations as $migration)
		{
			if ($this->migrateUp($migration) === false)
			{
				Blocks::log('Migration failed. All later migrations are canceled.', \CLogger::LEVEL_ERROR);
				return false;
			}
		}

		Blocks::log('Migrated up successfully.', \CLogger::LEVEL_INFO);
		return true;
	}

	/**
	 * @param $migrationName
	 * @return bool
	 */
	public function create($migrationName)
	{
		if (!preg_match('/^\w+$/', $migrationName))
		{
			Blocks::log('The name of the migration must contain letters, digits and/or underscore characters only.', \CLogger::LEVEL_ERROR);
			return false;
		}

		$name = 'm'.gmdate('ymd_His').'_'.$migrationName;
		$content = strtr($this->getTemplate(), array('{ClassName}' => $name));
		$file = IOHelper::normalizePathSeparators($this->migrationPath.'/'.$name.'.php');

		file_put_contents($file, $content);
		Blocks::log("New migration created successfully: ".$file, \CLogger::LEVEL_INFO);
		return true;
	}

	/**
	 * @param $class
	 * @return bool|null
	 */
	public function migrateUp($class)
	{
		if($class === $this->getBaseMigration())
		{
			return null;
		}

		Blocks::log('Applying migration: '.$class, \CLogger::LEVEL_INFO);

		$start = microtime(true);
		$migration = $this->instantiateMigration($class);

		if ($migration->up() !== false)
		{
			$this->getDbConnection()->createCommand()->insert($this->migrationTable, array(
				'version' => $class,
				'applyTime' => DateTimeHelper::currentTimeForDb(),
			));

			$time = microtime(true) - $start;
			Blocks::log('Applied migration: '.$class.' (time: '.sprintf("%.3f", $time).'s)', \CLogger::LEVEL_INFO);
			return true;
		}
		else
		{
			$time = microtime(true) - $start;
			Blocks::log('Failed to apply migration: '.$class.' (time: '.sprintf("%.3f", $time).'s)', \CLogger::LEVEL_ERROR);
			return false;
		}
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
	 * @throws Exception
	 * @return mixed
	 */
	protected function getDbConnection()
	{
		if ($this->_db !== null)
		{
			return $this->_db;
		}
		else if (($this->_db = Blocks::app()->getComponent($this->connectionID)) instanceof \CDbConnection)
		{
			return $this->_db;
		}
		else
		{
			throw new Exception(Blocks::t('MigrationCommand connectionId “{connectionId}” is invalid. Please make sure it refers to the ID of a DbConnection application component.', array('connectionId' => $this->connectionID)));
		}
	}

	/**
	 * @param null $limit
	 * @return mixed
	 */
	public function getMigrationHistory($limit = null)
	{
		$db = $this->getDbConnection();

		$query = $db->createCommand()
			->select('version, applyTime')
			->from($this->migrationTable)
			->order('version DESC');

		if ($limit !== null)
		{
			$query->limit($limit);
		}

		$migrations = $query->queryAll();

		// Convert the dates to DateTime objects
		foreach ($migrations as &$migration)
		{
			// TODO: MySQL specific.
			$migration['applyTime'] = DateTime::createFromFormat(DateTime::MYSQL_DATETIME, $migration['applyTime']);
		}

		return $migrations;
	}

	/**
	 * Gets migrations that have no been applied yet AND have a later timestamp than the current Blocks release.
	 *
	 * @return array
	 */
	public function getNewMigrations()
	{
		$applied = array();

		foreach ($this->getMigrationHistory() as $migration)
		{
			$applied[] = $migration['version'];
		}

		$migrations = array();
		$handle = opendir($this->migrationPath);
		$storedReleaseDate = Blocks::getStoredReleaseDate()->getTimestamp();

		while (($file = readdir($handle)) !== false)
		{
			if ($file === '.' || $file === '..')
			{
				continue;
			}

			$path = IOHelper::normalizePathSeparators($this->migrationPath.'/'.$file);
			$class = IOHelper::getFileName($path, false);

			// Have we already run this migration?
			if (in_array($class, $applied))
			{
				continue;
			}

			if (preg_match('/^m(\d\d)(\d\d)(\d\d)_(\d\d)(\d\d)(\d\d)_\w+\.php$/', $file, $matches))
			{
				// Check the migration timestamp against the Blocks release date
				$time = strtotime('20'.$matches[1].'-'.$matches[2].'-'.$matches[3].' '.$matches[4].':'.$matches[5].':'.$matches[6]);

				if ($time > $storedReleaseDate)
				{
					$migrations[] = $class;
				}
			}
		}

		closedir($handle);
		sort($migrations);
		return $migrations;
	}

	/**
	 * Returns the base migration name.
	 *
	 * @return string
	 */
	public function getBaseMigration()
	{
		return 'm000000_000000_base';
	}

	/**
	 * @return string
	 */
	protected function getTemplate()
	{
		return file_get_contents(Blocks::getPathOfAlias('app.components.updates.migrationtemplate').'.php');
	}
}
