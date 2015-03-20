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
 * migration skeleton files..
 *
 * The migration history is stored in a database table named [[migrationTable]]. The table will be automatically
 * created the first time this controller is executed, if it does not exist.
 *
 * Below are some common usages of this command:
 *
 * ~~~
 * # creates a new migration named 'create_user_table'
 * craft migrate create create_user_table
 *
 * # applies ALL new migrations
 * craft migrate
 * ~~~
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MigrateController extends \yii\console\controllers\MigrateController
{
	/**
	 * @var
	 */
	public $pluginHandle;

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
		throw new NotSupportedException('Redoing migrations are not supported.');
	}

	/**
	 * Craft doesn’t support running migrations up or down to a specific version.
	 *
	 * @param string $version
	 *
	 * @throws NotSupportedException
	 */
	public function actionTo($version)
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
	public function actionMark($version)
	{
		throw new NotSupportedException('Marking migrations is not supported.');
	}

	/**
	 * Used for creating a new migration, for either Craft or a plugin.
	 *
	 *    craft migrate create MigrationDescription --pluginHandle=pluginHandle
	 *
	 * If PluginHandle is omitted, the migration is created for Craft in craft/app/migrations. If it is available, the
	 * migration is created in craft/plugins/PluginHandle/migrations.
	 *
	 * The migration description can only contain letters, digits and/or underscore characters.
	 *
	 * @param string $name
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

		if ($this->pluginHandle)
		{
			if (!preg_match('/^\w+$/', $this->pluginHandle))
			{
				throw new Exception("The plugin handle should contain letters, digits and/or underscore characters only.");
			}

			// See if this is a valid plugin
			$this->_validatePlugin($this->pluginHandle);

			$name = 'm'.gmdate('ymd_His').'_'.StringHelper::toLowerCase($this->pluginHandle).'_'.$name;
			$migrationNameDesc = 'mYYMMDD_HHMMSS_pluginHandle_migrationName';

			$path = Craft::$app->path->getMigrationsPath($this->pluginHandle);

			if (!IOHelper::folderExists($path))
			{
				IOHelper::createFolder($path);
			}
		}
		else
		{
			$name = 'm'.gmdate('ymd_His').'_'.$name;
			$migrationNameDesc = 'mYYMMDD_HHMMSS_migrationName';
			$path = $this->migrationPath;
		}

		$migrationFile = $path.'/'.$name.'.php';

		if ($this->confirm("Create new migration '$migrationFile'?"))
		{
			$content = $this->renderFile(Craft::$app->migrations->getTemplate(), ['className' => $name, 'migrationNameDesc' => $migrationNameDesc]);
			IOHelper::writeToFile($migrationFile, $content);

			$this->stdout("New migration created successfully.\n", Console::FG_GREEN);
		}
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
