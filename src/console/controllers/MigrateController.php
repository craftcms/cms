<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\console\controllers;

use Craft;
use craft\app\base\Plugin;
use craft\app\base\PluginInterface;
use craft\app\db\MigrationManager;
use craft\app\helpers\IOHelper;
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
	 * @var string|PluginInterface|Plugin The handle of the plugin to use during migration operations, or the plugin itself
	 */
	public $plugin;

	/**
	 * @var MigrationManager The migration manager that will be used in this request
	 */
	private $_migrator;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		$this->templateFile = Craft::getAlias('@app/updates/migrationtemplate').'.php';
	}

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

	/**
	 * @inheritdoc
	 * @throws Exception if the 'plugin' option isn't valid
	 */
	public function beforeAction($action)
	{
		if (parent::beforeAction($action))
		{
			if (is_string($this->plugin))
			{
				$this->plugin = Craft::$app->getPlugins()->getPlugin($this->plugin);

				if ($this->plugin === null)
				{
					throw new Exception("The 'plugin' option must be set to an installed and enabled plugin's handle.");
				}
			}

			$this->migrationPath = $this->getMigrator()->migrationPath;

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function actionCreate($name)
	{
		if (!preg_match('/^\w+$/', $name))
		{
			throw new Exception("The migration name should contain letters, digits and/or underscore characters only.");
		}

		$name = 'm'.gmdate('ymd_His').'_'.$name;
		$file = $this->migrationPath.DIRECTORY_SEPARATOR.$name.'.php';

		if ($this->confirm("Create new migration '$file'?"))
		{
			$content = $this->renderFile(Craft::getAlias($this->templateFile), [
				'namespace' => $this->getMigrator()->migrationNamespace,
				'className' => $name
			]);

			IOHelper::writeToFile($file, $content);
			$this->stdout("New migration created successfully.\n", Console::FG_GREEN);
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Returns the migration manager that should be used for this request
	 *
	 * @return MigrationManager
	 */
	protected function getMigrator()
	{
		if ($this->_migrator === null)
		{
			if ($this->plugin !== null)
			{
				$this->_migrator = $this->plugin->getMigrator();
			}
			else
			{
				$this->_migrator = Craft::$app->getMigrator();
			}
		}

		return $this->_migrator;
	}

	/**
	 * @inheritdoc
	 */
	protected function createMigration($class)
	{
		return $this->getMigrator()->createMigration($class);
	}

	/**
	 * @inheritdoc
	 */
	protected function getNewMigrations()
	{
		return $this->getMigrator()->getNewMigrations();
	}

	/**
	 * @inheritdoc
	 */
	protected function getMigrationHistory($limit)
	{
		return $this->getMigrator()->getMigrationHistory($limit);
	}

	/**
	 * @inheritdoc
	 */
	protected function addMigrationHistory($version)
	{
		$this->getMigrator()->addMigrationHistory($version);
	}

	/**
	 * @inheritdoc
	 */
	protected function removeMigrationHistory($version)
	{
		$this->getMigrator()->removeMigrationHistory($version);
	}
}
