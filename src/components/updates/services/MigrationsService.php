<?php
namespace Blocks;

/**
 *
 */
class MigrationsService extends BaseApplicationComponent
{
	/**
	 * @var string the default command action. It defaults to 'up'.
	 */
	public $defaultAction = 'up';

	private $_migrationTable;

	/**
	 * @throws Exception
	 * @return bool|void
	 */
	public function init()
	{
		$migration = new MigrationRecord('install');
		$this->_migrationTable = $migration->getTableName();
	}

	/**
	 * @param null $plugin
	 * @return mixed
	 */
	public function runToTop($plugin = null)
	{
		if (($migrations = $this->getNewMigrations($plugin)) === array())
		{
			if ($plugin)
			{
				Blocks::log('No new migration(s) found for the plugin '.$plugin->getClassHandle().'. Your system is up-to-date.', \CLogger::LEVEL_INFO);
			}
			else
			{
				Blocks::log('No new migration(s) found for Blocks. Your system is up-to-date.', \CLogger::LEVEL_INFO);
			}

			return true;
		}

		$total = count($migrations);

		if ($plugin)
		{
			Blocks::log("Total $total new ".($total === 1 ? 'migration' : 'migrations')." to be applied for plugin ".$plugin->getClassHandle().":".PHP_EOL, \CLogger::LEVEL_INFO);
		}
		else
		{
			Blocks::log("Total $total new ".($total === 1 ? 'migration' : 'migrations')." to be applied for Blocks:".PHP_EOL, \CLogger::LEVEL_INFO);
		}

		foreach ($migrations as $migration)
		{
			Blocks::log($migration.PHP_EOL, \CLogger::LEVEL_INFO);
		}

		foreach ($migrations as $migration)
		{
			if ($this->migrateUp($migration, $plugin) === false)
			{
				if ($plugin)
				{
					Blocks::log('Migration failed for plugin '.$plugin->getClassHandle().'. All later '.$plugin->getClassHandle().' migrations are canceled.', \CLogger::LEVEL_ERROR);
				}
				else
				{
					Blocks::log('Migration failed for Blocks. All later Blocks migrations are canceled.', \CLogger::LEVEL_ERROR);
				}

				return false;
			}
		}

		if ($plugin)
		{
			Blocks::log($plugin->getClassHandle().' migrated up successfully.', \CLogger::LEVEL_INFO);
		}
		else
		{
			Blocks::log('Blocks migrated up successfully.', \CLogger::LEVEL_INFO);
		}

		return true;
	}

	/**
	 * @param      $class
	 * @param null $plugin
	 * @return bool|null
	 */
	public function migrateUp($class, $plugin = null)
	{
		if($class === $this->getBaseMigration())
		{
			return null;
		}

		if ($plugin)
		{
			Blocks::log('Applying migration: '.$class.' for plugin: '.$plugin->getClassHandle().'.', \CLogger::LEVEL_INFO);
		}
		else
		{
			Blocks::log('Applying migration: '.$class, \CLogger::LEVEL_INFO);
		}

		$start = microtime(true);
		$migration = $this->instantiateMigration($class, $plugin);

		if ($migration->up() !== false)
		{
			$column = $this->_getCorrectApplyTimeColumn();

			if ($plugin)
			{
				$pluginRecord = blx()->plugins->getPluginRecord($plugin);

				blx()->db->createCommand()->insert($this->_migrationTable, array(
					'version' => $class,
					$column => DateTimeHelper::currentTimeForDb(),
					'pluginId' => $pluginRecord->getPrimaryKey()
				));
			}
			else
			{
				blx()->db->createCommand()->insert($this->_migrationTable, array(
					'version' => $class,
					$column => DateTimeHelper::currentTimeForDb()
				));
			}

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
	 * @param      $class
	 * @param null $plugin
	 * @return mixed
	 */
	public function instantiateMigration($class, $plugin = null)
	{
		$file = IOHelper::normalizePathSeparators($this->getMigrationPath($plugin).$class.'.php');

		require_once($file);

		$class = __NAMESPACE__.'\\'.$class;
		$migration = new $class;
		$migration->setDbConnection(blx()->db);

		return $migration;
	}

	/**
	 * @param null $plugin
	 * @param null $limit
	 * @return mixed
	 */
	public function getMigrationHistory($plugin = null, $limit = null)
	{
		$column = $this->_getCorrectApplyTimeColumn();

		if ($plugin === 'all')
		{
			$query = blx()->db->createCommand()
				->select('version, '.$column)
				->from($this->_migrationTable)
				->order('version DESC');
		}
		else if ($plugin)
		{
			$pluginRecord = blx()->plugins->getPluginRecord($plugin);

			$query = blx()->db->createCommand()
				->select('version, '.$column)
				->from($this->_migrationTable)
				->where('pluginId = :pluginId', array(':pluginId' => $pluginRecord->getPrimaryKey()))
				->order('version DESC');
		}
		else
		{
			$query = blx()->db->createCommand()
				->select('version, '.$column)
				->from($this->_migrationTable)
				->where('pluginId IS NULL')
				->order('version DESC');
		}

		if ($limit !== null)
		{
			$query->limit($limit);
		}

		$migrations = $query->queryAll();

		// Convert the dates to DateTime objects
		foreach ($migrations as &$migration)
		{
			$column = $this->_getCorrectApplyTimeColumn();

			// TODO: MySQL specific.
			$migration['applyTime'] = DateTime::createFromFormat(DateTime::MYSQL_DATETIME, $migration[$column]);
		}

		return $migrations;
	}

	/**
	 * Gets migrations that have no been applied yet AND have a later timestamp than the current Blocks release.
	 *
	 * @param $plugin
	 *
	 * @return array
	 */
	public function getNewMigrations($plugin = null)
	{
		$applied = array();
		$migrationPath = $this->getMigrationPath($plugin);

		foreach ($this->getMigrationHistory($plugin) as $migration)
		{
			$applied[] = $migration['version'];
		}

		$migrations = array();
		$handle = opendir($migrationPath);

		if ($plugin)
		{
			$pluginRecord = blx()->plugins->getPluginRecord($plugin);
			$storedDate = $pluginRecord->installDate->getTimestamp();
		}
		else
		{
			$storedDate = Blocks::getStoredReleaseDate()->getTimestamp();
		}

		while (($file = readdir($handle)) !== false)
		{
			if ($file[0] === '.')
			{
				continue;
			}

			$path = IOHelper::normalizePathSeparators($migrationPath.$file);
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

				if ($time > $storedDate)
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
	 * @param null $plugin
	 * @return string
	 * @throws Exception
	 */
	public function getMigrationPath($plugin = null)
	{
		if ($plugin)
		{
			$path = blx()->path->getMigrationsPath($plugin->getClassHandle());
		}
		else
		{
			$path = blx()->path->getMigrationsPath();
		}

		if (!IOHelper::folderExists($path))
		{
			if (!IOHelper::createFolder($path))
			{
				throw new Exception(Blocks::t('Tried to create the migration folder at “{folder}”, but could not.', array('folder' => $path)));
			}
		}

		return $path;
	}

	/**
	 * @return string
	 */
	public function getTemplate()
	{
		return file_get_contents(Blocks::getPathOfAlias('app.components.updates.migrationtemplate').'.php');
	}

	/**
	 * TODO: Deprecate after next breakpoint.
	 *
	 * @return string
	 */
	private function _getCorrectApplyTimeColumn()
	{
		$migrationsTable = blx()->db->schema->getTable('{{migrations}}');

		$applyTimeColumn = 'apply_time';

		if ($migrationsTable->getColumn('applyTime') !== null)
		{
			$applyTimeColumn = 'applyTime';
		}

		return $applyTimeColumn;
	}
}
