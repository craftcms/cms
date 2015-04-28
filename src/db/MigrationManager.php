<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\db;

use Craft;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\IOHelper;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\db\MigrationInterface;
use yii\di\Instance;

/**
 * MigrationManager manages a set of migrations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MigrationManager extends Component
{
	/**
	 * The name of the dummy migration that marks the beginning of the whole migration history.
	 */
	const BASE_MIGRATION = 'm000000_000000_base';

	// Properties
	// =========================================================================

	/**
	 * @var string The namespace that the migration classes are in
	 */
	public $migrationNamespace;

	/**
	 * @var string The path of the migrations folder
	 */
	public $migrationPath;

	/**
	 * @var \yii\db\Connection|array|string The DB connection object or the application component ID of the DB connection
	 */
	public $db = 'db';

	/**
	 * @var string The migrations table name
	 */
	public $migrationTable = '{{%migrations}}';

	/**
	 * @var array Values that should always be present when inserting and selecting data from the migrations table
	 */
	public $fixedColumnValues = [];

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if ($this->migrationPath === null)
		{
			throw new InvalidConfigException('The migration folder path has not been set.');
		}

		$this->migrationPath = Craft::getAlias($this->migrationPath);
		IOHelper::ensureFolderExists($this->migrationPath);

		$this->db = Instance::ensure($this->db, \yii\db\Connection::className());
	}

	/**
	 * Creates a new migration instance.
	 *
	 * @param string $name The migration name
	 * @return MigrationInterface|\yii\db\Migration The migration instance
	 */
	public function createMigration($name)
	{
		$file = $this->migrationPath."/$name.php";
		$class = $this->migrationNamespace."\\$name";
		require_once($file);
		return new $class;
	}

	/**
	 * Upgrades the application by applying new migrations.
	 *
	 * @param integer $limit The number of new migrations to be applied. If 0, it means
	 * applying all available new migrations.
	 * @return boolean Whether the migrations were applied successfully
	 */
	public function up($limit = 0)
	{
		// This might take a while
		Craft::$app->getConfig()->maxPowerCaptain();

		$migrationNames = $this->getNewMigrations();

		if (empty($migrationNames))
		{
			Craft::info('No new migration found. Your system is up-to-date.');
			return true;
		}

		$total = count($migrationNames);
		$limit = (int) $limit;

		if ($limit > 0)
		{
			$migrationNames = array_slice($migrationNames, 0, $limit);
		}

		$n = count($migrationNames);

		if ($n === $total)
		{
			$logMessage = "Total $n new " . ($n === 1 ? 'migration' : 'migrations') . " to be applied:";
		}
		else
		{
			$logMessage = "Total $n out of $total new " . ($total === 1 ? 'migration' : 'migrations') . " to be applied:";
		}

		foreach ($migrationNames as $migrationName)
		{
			$logMessage .= "\n\t$migrationName";
		}

		Craft::info($logMessage);

		foreach ($migrationNames as $migrationName)
		{
			if (!$this->migrateUp($migrationName))
			{
				Craft::error('Migration failed. The rest of the migrations are canceled.');
				return false;
			}
		}

		Craft::info('Migrated up successfully.');
		return true;
	}

	/**
	 * Downgrades the application by reverting old migrations.
	 *
	 * @param integer|string $limit The number of migrations to be reverted. Defaults to 1,
	 * meaning the last applied migration will be reverted. If set to "all", all migrations will be reverted.
	 *
	 * @return boolean Whether the migrations were reverted successfully
	 */
	public function down($limit = 1)
	{
		// This might take a while
		Craft::$app->getConfig()->maxPowerCaptain();

		if ($limit === 'all' || $limit < 1)
		{
			$limit = null;
		}
		else
		{
			$limit = (int) $limit;
		}

		$migrationNames = array_keys($this->getMigrationHistory($limit));

		if (empty($migrationNames))
		{
			Craft::info('No migration has been done before.');
			return true;
		}

		$n = count($migrationNames);
		$logMessage = "Total $n ".($n === 1 ? 'migration' : 'migrations')." to be reverted:";

		foreach ($migrationNames as $migrationName)
		{
			$logMessage .= "\n\t$migrationName";
		}

		Craft::info($logMessage);

		foreach ($migrationNames as $migrationName)
		{
			if (!$this->migrateDown($migrationName))
			{
				Craft::error('Migration failed. The rest of the migrations are canceled.');
				return false;
			}
		}

		Craft::info('Migrated down successfully.');
		return true;
	}

	/**
	 * Upgrades with the specified migration.
	 *
	 * @param string|MigrationInterface|\yii\db\Migration $migration The name of the migration to apply, or the migration itself
	 * @return boolean Whether the migration was applied successfully
	 */
	public function migrateUp($migration)
	{
		list($migrationName, $migration) = $this->_normalizeMigration($migration);

		if ($migrationName === self::BASE_MIGRATION)
		{
			return true;
		}

		/** @var MigrationInterface|\yii\db\Migration $migration */
		$migration = Instance::ensure($migration, 'yii\db\MigrationInterface');

		Craft::info("Applying $migrationName");

		$isConsoleRequest = Craft::$app->getRequest()->getIsConsoleRequest();

		if (!$isConsoleRequest)
		{
			ob_start();
		}

		$start = microtime(true);
		$success = ($migration->up() !== false);
		$time = microtime(true) - $start;

		if ($success)
		{
			Craft::info("Applied $migrationName (time: ".sprintf("%.3f", $time)."s)");
			$this->addMigrationHistory($migrationName);
		}
		else
		{
			Craft::error("Failed to apply $migrationName (time: ".sprintf("%.3f", $time)."s)");
		}

		if (!$isConsoleRequest)
		{
			$output = ob_get_clean();

			if ($success)
			{
				Craft::info($output);
			}
			else
			{
				Craft::error($output);
			}
		}

		return $success;
	}

	/**
	 * Downgrades with the specified migration.
	 *
	 * @param string|MigrationInterface|\yii\db\Migration $migration The name of the migration to revert, or the migration itself
	 * @return boolean Whether the migration was reverted successfully
	 */
	public function migrateDown($migration)
	{
		list($migrationName, $migration) = $this->_normalizeMigration($migration);

		if ($migrationName === self::BASE_MIGRATION)
		{
			return true;
		}

		/** @var MigrationInterface|\yii\db\Migration $migration */
		$migration = Instance::ensure($migration, 'yii\db\MigrationInterface');

		Craft::info("Reverting $migrationName");

		$isConsoleRequest = Craft::$app->getRequest()->getIsConsoleRequest();

		if (!$isConsoleRequest)
		{
			ob_start();
		}

		$start = microtime(true);
		$success = ($migration->down() !== false);
		$time = microtime(true) - $start;

		if ($success)
		{
			Craft::info("Reverted $migrationName (time: ".sprintf("%.3f", $time)."s)");
			$this->removeMigrationHistory($migrationName);
		}
		else
		{
			Craft::error("Failed to revert $migrationName (time: ".sprintf("%.3f", $time)."s)");
		}

		if (!$isConsoleRequest)
		{
			$output = ob_get_clean();

			if ($success)
			{
				Craft::info($output);
			}
			else
			{
				Craft::error($output);
			}
		}

		return $success;
	}

	/**
	 * Returns the migration history.
	 *
	 * @param integer $limit The maximum number of records in the history to be returned. `null` for "no limit".
	 * @return array The migration history
	 */
	public function getMigrationHistory($limit = null)
	{
		$history = $this->_createMigrationQuery()
			->limit($limit)
			->pairs($this->db);
		unset($history[self::BASE_MIGRATION]);
		return $history;
	}

	/**
	 * Adds a new migration entry to the history.
	 *
	 * @param string $name The migration name
	 */
	public function addMigrationHistory($name)
	{
		Craft::$app->getDb()->createCommand()->insert(
			$this->migrationTable,
			array_merge($this->fixedColumnValues, [
				'name' => $name,
				'applyTime' => DateTimeHelper::currentTimeForDb()
			])
		)->execute();
	}

	/**
	 * Removes an existing migration from the history.
	 *
	 * @param string $name The migration name
	 */
	public function removeMigrationHistory($name)
	{
		Craft::$app->getDb()->createCommand()->delete(
			$this->migrationTable,
			array_merge($this->fixedColumnValues, [
				'name' => $name
			])
		)->execute();
	}

	/**
	 * Returns whether a given migration has been applied.
	 *
	 * @param string $name The migration name
	 * @return boolean Whether the migration has been applied
	 */
	public function hasRun($name)
	{
		return $this->_createMigrationQuery()
			->andWhere(['name' => $name])
			->exists($this->db);
	}

	/**
	 * Returns the migrations that are not applied.
	 *
	 * @return array The list of new migrations
	 */
	public function getNewMigrations()
	{
		$migrations = [];
		$history = $this->getMigrationHistory();
		$handle = opendir($this->migrationPath);

		while (($file = readdir($handle)) !== false)
		{
			if ($file === '.' || $file === '..')
			{
				continue;
			}

			$path = $this->migrationPath.DIRECTORY_SEPARATOR.$file;

			if (preg_match('/^(m\d{6}_\d{6}_.*?)\.php$/', $file, $matches) && is_file($path) && !isset($history[$matches[1]]))
			{
				$migrations[] = $matches[1];
			}
		}

		closedir($handle);
		sort($migrations);
		return $migrations;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Normalizes the $migration argument passed to [[migrateUp()]] and [[migrateDown()]].
	 *
	 * @param string|MigrationInterface|\yii\db\Migration $migration The name of the migration to apply, or the migration itself
	 * @return array
	 */
	private function _normalizeMigration($migration)
	{
		if (is_string($migration))
		{
			$migrationName = $migration;
			$migration = $this->createMigration($migration);
		}
		else
		{
			$classParts = explode('\\', $migration::className());
			$migrationName = array_pop($classParts);
		}

		return [$migrationName, $migration];
	}

	/**
	 * Returns a Query object prepped for retrieving migrations.
	 *
	 * @return Query The query
	 */
	private function _createMigrationQuery()
	{
		// TODO: Remove after next breakpoint
		if (version_compare(Craft::$app->getInfo('version'), '3.0', '<'))
		{
			$nameColumn = 'version as name';

			if ($this->fixedColumnValues === ['type' => 'app'])
			{
				$condition = ['pluginId' => null];
			}
			else
			{
				$condition = ['pluginId' => $this->fixedColumnValues['pluginId']];
			}
		}
		else
		{
			$nameColumn = 'name';
			$condition = $this->fixedColumnValues;
		}

		return (new Query())
			->select("$nameColumn, applyTime")
			->from($this->migrationTable)
			->orderBy('name desc')
			->where($condition);
	}
}
