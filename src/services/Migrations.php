<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\BasePlugin;
use craft\app\dates\DateTime;
use craft\app\db\Command;
use craft\app\db\Query;
use craft\app\errors\Exception;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\IOHelper;
use craft\app\records\Migration as MigrationRecord;
use yii\base\Component;

/**
 * Class Migrations service.
 *
 * An instance of the Migrations service is globally accessible in Craft via [[Application::migrations `Craft::$app->migrations`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Migrations extends Component
{
	// Properties
	// =========================================================================

	/**
	 * The default command action. It defaults to 'up'.
	 *
	 * @var string
	 */
	public $defaultAction = 'up';

	/**
	 * @var
	 */
	private $_migrationTable;

	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application component.
	 *
	 * @throws Exception
	 * @return bool|null
	 */
	public function init()
	{
		$this->_migrationTable = MigrationRecord::tableName();
	}

	/**
	 * @param BasePlugin|null $plugin
	 *
	 * @return mixed
	 */
	public function runToTop($plugin = null)
	{
		// This might take a while
		Craft::$app->config->maxPowerCaptain();

		if (($migrations = $this->getNewMigrations($plugin)) === [])
		{
			if ($plugin)
			{
				Craft::info('No new migration(s) found for the plugin '.$plugin::className().'. Your system is up-to-date.', __METHOD__);
			}
			else
			{
				Craft::info('No new migration(s) found for Craft. Your system is up-to-date.', __METHOD__);
			}

			return true;
		}

		$total = count($migrations);

		if ($plugin)
		{
			Craft::info('Total '.$total.' new '.($total === 1 ? 'migration' : 'migrations').' to be applied for plugin '.$plugin::className().':', __METHOD__);
		}
		else
		{
			Craft::info('Total '.$total.' new '.($total === 1 ? 'migration' : 'migrations').' to be applied for Craft:', __METHOD__);
		}

		foreach ($migrations as $migration)
		{
			Craft::info($migration, __METHOD__);
		}

		foreach ($migrations as $migration)
		{
			// Refresh the DB cache
			Craft::$app->getDb()->getSchema()->refresh();

			if ($this->migrateUp($migration, $plugin) === false)
			{
				if ($plugin)
				{
					Craft::error('Migration failed for plugin '.$plugin::className().'. All later '.$plugin::className().' migrations are canceled.', __METHOD__);
				}
				else
				{
					Craft::error('Migration failed for Craft. All later Craft migrations are canceled.', __METHOD__);
				}

				// Refresh the DB cache
				Craft::$app->getDb()->getSchema()->refresh();

				return false;
			}
		}

		if ($plugin)
		{
			Craft::info($plugin::className().' migrated up successfully.', __METHOD__);
		}
		else
		{
			Craft::info('Craft migrated up successfully.', __METHOD__);
		}

		// Refresh the DB cache
		Craft::$app->getDb()->getSchema()->refresh();

		return true;
	}

	/**
	 * @param      $class
	 * @param null $plugin
	 *
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
			Craft::info('Applying migration: '.$class.' for plugin: '.$plugin::className(), __METHOD__);
		}
		else
		{
			Craft::info('Applying migration: '.$class, __METHOD__);
		}

		$start = microtime(true);
		$migration = $this->instantiateMigration($class, $plugin);

		if ($migration->up() !== false)
		{
			$this->addMigrationHistory($class, $plugin);

			$time = microtime(true) - $start;
			Craft::info('Applied migration: '.$class.' (time: '.sprintf("%.3f", $time).'s)', __METHOD__);
			return true;
		}
		else
		{
			$time = microtime(true) - $start;
			Craft::error('Failed to apply migration: '.$class.' (time: '.sprintf("%.3f", $time).'s)', __METHOD__);
			return false;
		}
	}

	/**
	 * Adds a row to the `craft_migrations` table for either Craft or a plugin.
	 *
	 * @param string      $migrationClassName The name of the migration class to add.
	 * @param string|null $plugin             The plugin handle of the migration, if any.
	 *
	 * @throws \yii\db\Exception
	 */
	public function addMigrationHistory($migrationClassName, $plugin = null)
	{
		if ($plugin)
		{
			$pluginInfo = Craft::$app->plugins->getStoredPluginInfo($plugin);

			Craft::$app->getDb()->createCommand()->insert($this->_migrationTable, [
				'version' => $migrationClassName,
				'applyTime' => DateTimeHelper::currentTimeForDb(),
				'pluginId' => $pluginInfo['id']
			])->execute();
		}
		else
		{
			Craft::$app->getDb()->createCommand()->insert($this->_migrationTable, [
				'version' => $migrationClassName,
				'applyTime' => DateTimeHelper::currentTimeForDb()
			])->execute();
		}
	}

	/**
	 * @param       $class
	 * @param  null $plugin
	 *
	 * @throws Exception
	 * @return mixed
	 */
	public function instantiateMigration($class, $plugin = null)
	{
		$file = IOHelper::normalizePathSeparators($this->getMigrationPath($plugin).'/'.$class.'.php');

		if (!IOHelper::fileExists($file) || !IOHelper::isReadable($file))
		{
			Craft::error('Tried to find migration file '.$file.' for class '.$class.', but could not.', __METHOD__);
			throw new Exception(Craft::t('app', 'Could not find the requested migration file.'));
		}

		require_once($file);

		if ($plugin)
		{
			$class = "craft\\plugins\\".$plugin::className()."\\migrations\\".$class;
		}
		else
		{
			$class = "craft\\app\\migrations\\{$class}";
		}

		/* @var $migration craft\app\db\Migration */
		$migration = new $class;
		$migration->db = Craft::$app->getDb();

		return $migration;
	}

	/**
	 * @param null $plugin
	 * @param null $limit
	 *
	 * @return mixed
	 */
	public function getMigrationHistory($plugin = null, $limit = null)
	{
		$query = $this->_createMigrationQuery($plugin);

		if ($limit !== null)
		{
			$query->limit($limit);
		}

		$migrations = $query->all();

		// Convert the dates to DateTime objects
		foreach ($migrations as &$migration)
		{
			// TODO: MySQL specific.
			$migration['applyTime'] = DateTime::createFromFormat(DateTime::MYSQL_DATETIME, $migration['applyTime']);
		}

		return $migrations;
	}

	/**
	 * Returns whether a given migration has been run.
	 *
	 * @param string      $version
	 * @param string|null $plugin
	 *
	 * @return bool
	 */
	public function hasRun($version, $plugin = null)
	{
		return $this->_createMigrationQuery($plugin)
			->andWhere('version = :version', [':version' => $version])
			->exists();
	}

	/**
	 * Gets migrations that have no been applied yet AND have a later timestamp than the current Craft release.
	 *
	 * @param $plugin
	 *
	 * @return array
	 */
	public function getNewMigrations($plugin = null)
	{
		$migrations = [];
		$migrationPath = $this->getMigrationPath($plugin);

		if (IOHelper::folderExists($migrationPath) && IOHelper::isReadable($migrationPath))
		{
			$applied = [];

			foreach ($this->getMigrationHistory($plugin) as $migration)
			{
				$applied[] = $migration['version'];
			}

			$handle = opendir($migrationPath);

			while (($file = readdir($handle)) !== false)
			{
				if ($file[0] === '.')
				{
					continue;
				}

				$path = IOHelper::normalizePathSeparators($migrationPath.'/'.$file);
				$class = IOHelper::getFilename($path, false);

				// Have we already run this migration?
				if (in_array($class, $applied))
				{
					continue;
				}

				if (preg_match('/^m(\d\d)(\d\d)(\d\d)_(\d\d)(\d\d)(\d\d)_\w+\.php$/', $file, $matches))
				{
					$migrations[] = $class;
				}
			}

			closedir($handle);
			sort($migrations);
		}

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
	 *
	 * @throws Exception
	 * @return string
	 */
	public function getMigrationPath($plugin = null)
	{
		if ($plugin)
		{
			$path = Craft::$app->path->getMigrationsPath($plugin::className());
		}
		else
		{
			$path = Craft::$app->path->getMigrationsPath();
		}

		return $path;
	}

	/**
	 * @return string
	 */
	public function getTemplate()
	{
		return Craft::getAlias('@app/updates/migrationtemplate').'.php';
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a Query object prepped for retrieving migrations.
	 *
	 * @param string|null $plugin
	 *
	 * @return Query
	 */
	private function _createMigrationQuery($plugin = null)
	{
		$query = (new Query())
			->select(['version', 'applyTime'])
			->from($this->_migrationTable)
			->orderBy('version desc');

		if ($plugin)
		{
			if ($plugin != 'all')
			{
				$pluginInfo = Craft::$app->plugins->getStoredPluginInfo($plugin);
				$query->where('pluginId = :pluginId', [':pluginId' => $pluginInfo['id']]);
			}
		}
		else
		{
			$query->where('pluginId is null');
		}

		return $query;
	}
}
