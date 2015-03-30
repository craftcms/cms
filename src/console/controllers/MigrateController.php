<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\console\controllers;

use Craft;
use craft\app\base\BasePlugin;
use craft\app\helpers\IOHelper;
use craft\app\helpers\StringHelper;
use yii\base\NotSupportedException;
use yii\console\controllers\BaseMigrateController;
use yii\console\Exception;
use yii\helpers\Console;

/**
 * Manages Craft and plugin migrations.
 *
 * A migration means a set of persistent changes to the application environment that is shared among different
 * developers. For example, in an application backed by a database, a migration may refer to a set of changes to
 * the database, such as creating a new table, adding a new table column.
 *
 * This controllers provides support for tracking the migration history, updating migrations, and creating new
 * migration skeleton files.
 *
 * The migration history is stored in a database table named [[migrationTable]]. The table will be automatically
 * created the first time this controller is executed, if it does not exist.
 *
 * Below are some common usages of this command:
 *
 * ~~~
 * # creates a new migration named 'create_user_table' for a plugin with the handle pluginHandle.
 * craft migrate/create create_user_table --plugin=pluginHandle
 *
 * # applies ALL new migrations for a plugin with the handle pluginHandle
 * craft migrate up --plugin=pluginHandle
 * ~~~
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MigrateController extends BaseMigrateController
{
	// Properties
	// =========================================================================

	/**
	 * The handle of the plugin to use during migration operations.
	 *
	 * @var
	 */
	public $plugin;

	/**
	 * @inheritdoc
	 */
	public function options($actionID)
	{
		return array_merge(
			parent::options($actionID),
			['plugin']
		);
	}

	// Public Methods
	// =========================================================================

	/**
	 * @param \yii\base\Action $action
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function beforeAction($action)
	{
		if ($this->plugin)
		{
			if (!preg_match('/^\w+$/', $this->plugin))
			{
				throw new Exception("The plugin handle should contain letters, digits and/or underscore characters only.");
			}

			// See if this is a valid plugin
			$this->_validatePlugin($this->plugin);

			$path = Craft::$app->path->getMigrationsPath($this->plugin);

			$this->migrationPath = $path;
		}

		return parent::beforeAction($action);
	}

	/**
	 * Craft doesn’t support down migrations.
	 *
	 * @param int $limit
	 *
	 * @return int|void
	 * @throws NotSupportedException
	 */
	public function actionDown($limit = 1)
	{
		throw new NotSupportedException('Down migrations are not supported.');
	}

	/**
	 * Craft doesn’t support redoing migrations.
	 *
	 * @param int $limit
	 *
	 * @return int|void
	 * @throws NotSupportedException
	 */
	public function actionRedo($limit = 1)
	{
		throw new NotSupportedException('Redoing migrations is not supported.');
	}

	/**
	 * Craft doesn’t support running migrations up or down to a specific version.
	 *
	 * @param string $version
	 *
	 * @throws NotSupportedException
	 */
	public function actionTo($version = null)
	{
		throw new NotSupportedException('Running migrations to a specific point is not supported.');
	}

	/**
	 * Craft doesn’t support changing migration history.
	 *
	 * @param string $version
	 *
	 * @return int|void
	 * @throws NotSupportedException
	 */
	public function actionMark($version = null)
	{
		throw new NotSupportedException('Marking migrations is not supported.');
	}

	/**
	 * Used for creating a new migration, for either Craft or a plugin.
	 *
	 *    craft migrate/create MigrationDescription --plugin=pluginHandle
	 *
	 * If --plugin is omitted, the migration is created for Craft in craft/app/migrations. If it is available, the
	 * migration is created in craft/plugins/pluginHandle/migrations.
	 *
	 * The migration description can only contain letters, digits and/or underscore characters.
	 *
	 * @param string $name The description of the migration to create.
	 *
	 * @return int
	 * @throws Exception
	 */
	public function actionCreate($name)
	{
		if (!preg_match('/^\w+$/', $name))
		{
			throw new Exception("The migration name should contain letters, digits and/or underscore characters only.");
		}

		if ($this->plugin)
		{
			$name = 'm'.gmdate('ymd_His').'_'.StringHelper::toLowerCase($this->plugin).'_'.$name;
			$migrationNameDesc = 'mYYMMDD_HHMMSS_pluginHandle_migrationName';
			$namespace = "craft\\plugins\\{$this->plugin}\\migrations;";
		}
		else
		{
			$name = 'm'.gmdate('ymd_His').'_'.$name;
			$migrationNameDesc = 'mYYMMDD_HHMMSS_migrationName';
			$namespace = "craft\\app\\migrations;";
		}

		$migrationFile = $this->migrationPath.'/'.$name.'.php';

		if ($this->confirm("Create new migration '$migrationFile'?"))
		{
			$content = $this->renderFile(Craft::$app->migrations->getTemplate(), ['className' => $name, 'migrationNameDesc' => $migrationNameDesc, 'namespace' => $namespace]);
			IOHelper::writeToFile($migrationFile, $content);

			$this->stdout("New migration created successfully.\n", Console::FG_GREEN);
			return self::EXIT_CODE_NORMAL;
		}
	}

	/**
	 * Used for running any new migrations for either Craft or a plugin.
	 *
	 *     craft migrate/up --plugin=pluginHandle
	 *
	 * If --plugin is omitted, any new migrations that haven't ran yet in craft/app/migrations will be ran. If it is
	 * available, any new migrations in craft/plugins/pluginHandle/migrations that haven't ran yet, will run.
	 *
	 * @param int $limit
	 *
	 * @return int
	 * @throws Exception
	 */
	public function actionUp($limit = 0)
	{
		if ($this->plugin)
		{
			$app = $this->_validatePlugin($this->plugin);
			$displayName = $app::className();
		}
		else
		{
			$app = null;
			$displayName = 'Craft';
		}

		if (Craft::$app->migrations->runToTop($app))
		{
			$this->stdout("Migrated ".$displayName." to top successfully.\n", Console::FG_GREEN);
			return self::EXIT_CODE_NORMAL;
		}
		else
		{
			$this->stderr("There was a problem migrating ".$displayName." to top.  Check the logs for the specific error.\n", Console::FG_RED);
			return self::EXIT_CODE_ERROR;
		}
	}

	/**
	 * Used for seeing which migrations haven't already been ran in Craft or a plugin.
	 *
	 *     craft migrate/history --plugin=pluginHandle
	 *
	 * If --plugin is omitted, it will display all of Craft's migrations that have already ran. If it is available,
	 * it will display all of pluginHandle's migrations that have already ran.
	 *
	 * @param int $limit The number of migrations to return. Defaults to 10.
	 *
	 * @throws Exception
	 */
	public function actionHistory($limit = 10)
	{
		if ($limit === 'all')
		{
			$limit = null;
		}
		else
		{
			$limit = (int)$limit;

			if ($limit < 1)
			{
				throw new Exception("The limit must be greater than 0.");
			}
		}

		if ($this->plugin)
		{
			$plugin = $this->_validatePlugin($this->plugin);
			$displayName = $plugin::className();
		}
		else
		{
			$displayName = 'Craft';
		}

		$migrations = $this->getMigrationHistory($limit);

		if (empty($migrations))
		{
			$this->stdout("No migrations have been done for {$displayName} before.\n", Console::FG_YELLOW);

		}
		else
		{
			$n = count($migrations);

			if ($limit > 0)
			{
				$this->stdout("Showing the last {$n} applied ".($n === 1 ? 'migration' : 'migrations')." to {$displayName}:\n", Console::FG_YELLOW);
			}
			else
			{
				$this->stdout("Total {$n} " . ($n === 1 ? 'migration has' : 'migrations have') . " been applied before to {$displayName}:\n", Console::FG_YELLOW);
			}

			foreach ($migrations as $key => $value)
			{
				$this->stdout("\t(" . date('Y-m-d H:i:s', $value['applyTime']) . ') '.$value['version']."\n");
			}
		}
	}

	/**
	 * Used for seeing any new migrations that haven't run yet in Craft or a plugin.
	 *
	 *     craft migrate/new --plugin=pluginHandle
	 *
	 * If --plugin is omitted, it will display any new Craft migrations that have not run, yet. If it is available,
	 * it will display all of PluginHandle's migrations that have not run, yet.
	 *
	 * @param int $limit
	 *
	 * @throws Exception
	 */
	public function actionNew($limit = 10)
	{
		if ($limit === 'all')
		{
			$limit = null;
		}
		else
		{
			$limit = (int) $limit;

			if ($limit < 1)
			{
				throw new Exception("The limit must be greater than 0.");
			}
		}

		if ($this->plugin)
		{
			$plugin = $this->_validatePlugin($this->plugin);
			$displayName = $plugin::className();
		}
		else
		{
			$displayName = 'Craft';
		}

		$migrations = $this->getNewMigrations();

		if (empty($migrations))
		{
			$this->stdout("No new {$displayName} migrations found. Your system is up-to-date.\n", Console::FG_GREEN);
		}
		else
		{
			$n = count($migrations);

			if ($limit && $n > $limit)
			{
				$migrations = array_slice($migrations, 0, $limit);
				$this->stdout("Showing $limit out of $n new {$displayName} ".($n === 1 ? 'migration' : 'migrations').":\n", Console::FG_YELLOW);
			}
			else
			{
				$this->stdout("Found $n new {$displayName} ".($n === 1 ? 'migration' : 'migrations').":\n", Console::FG_YELLOW);
			}

			foreach ($migrations as $migration)
			{
				$this->stdout("\t".$migration."\n");
			}
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Overriding this because it's guaranteed to be there in Craft.
	 */
	protected function createMigrationHistoryTable()
	{
		return true;
	}

	/**
	 * Gets any new migrations for either Craft or a plugin.
	 *
	 * Overriding Yii's implementation with Craft specific logic.
	 *
	 * @return array
	 */
	protected function getNewMigrations()
	{
		return Craft::$app->migrations->getNewMigrations($this->plugin);
	}

	/**
	 * Returns the migration history.
	 *
	 * @param int $limit the maximum number of records in the history to be returned. `null` for "no limit".
	 *
	 * @return array the migration history
	 */
	protected function getMigrationHistory($limit)
	{
		$migrations = Craft::$app->migrations->getMigrationHistory($this->plugin, $limit);

		// Convert the dates to Unix timestamps
		foreach ($migrations as &$migration)
		{
			$migration['applyTime'] = $migration['applyTime']->getTimestamp();
		}

		return $migrations;
	}

	/**
	 * Adds new migration entry to the history.
	 *
	 * @param string $version migration version name.
	 */
	protected function addMigrationHistory($version)
	{
		return Craft::$app->migrations->addMigrationHistory($version, $this->plugin);
	}

	/**
	 * Removes existing migration from the history.
	 *
	 * @param string $version migration version name.
	 *
	 * @throws NotSupportedException
	 */
	protected function removeMigrationHistory($version)
	{
		throw new NotSupportedException();
}

	// Private Methods
	// =========================================================================

	/**
	 * Given a plugin handle, will retrieve it, or display an error if it doesn't exist or is disabled.
	 *
	 * @param string $pluginHandle The handle of the plugin to search for.
	 *
	 * @return BasePlugin|int
	 * @throws Exception
	 */
	private function _validatePlugin($pluginHandle)
	{
		$plugin = Craft::$app->plugins->getPlugin($pluginHandle);

		if (!$plugin)
		{
			throw new Exception("Could not find an installed and enabled plugin with the handle '$pluginHandle'");
		}

		return $plugin;
	}
}
