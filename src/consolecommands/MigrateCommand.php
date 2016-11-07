<?php
namespace Craft;

/**
 * MigrateCommand is a command for managing Craft and plugin database migrations.
 *
 * MigrateCommand supports the following options.
 *
 * Creates a new migration in your plugins’ migrations/ folder with the given MigrationDescription:
 *
 * ```bash
 * yiic migrate create MigrationDescription PluginHandle
 * ```
 *
 * Shows a list of migrations that have already been ran for this plugin:
 *
 * ```bash
 * yiic migrate history PluginHandle
 * ```
 *
 * Shows a list of migrations that have not been applied yet, but should be:
 *
 * ```bash
 * yiic migrate new PluginHandle
 * ```
 *
 * Runs all new migrations for a plugin:
 *
 * ```bash
 * yiic migrate up PluginHandle
 * ```
 *
 * Note that PluginHandle is optional in these examples and if it is not presented, the command will run against Craft.
 *
 * Craft explicitly disables support for Yii's "down", "redo" and "mark" actions.
 *
 * This command will exit with the following exit codes:
 *
 * - `0` on success
 * - `1` on general error
 * - `2` on failed migration.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.consolecommands
 * @since     1.0
 */
class MigrateCommand extends \MigrateCommand
{
	// Public Methods
	// =========================================================================

	/**
	 * Used for ensuring a plugin's migration exists if the action is "create".
	 *
	 * This method is invoked right before an action is to be executed.
	 *
	 * @param string $action The name of the action to run.
	 * @param array  $params The parameters to be passed to the action's method.
	 *
	 * @return bool Whether the action should be executed or not.
	 */
	public function beforeAction($action, $params)
	{
		if ($action == 'create')
		{
			// If the 1st dimension is the 1st index, then we know it's a plugin. No need to make them specify the path.
			if (isset($params[0][1]))
			{
				$plugin = $params[0][1];

				$path = craft()->path->getMigrationsPath($plugin);

				if (!IOHelper::folderExists($path))
				{
					echo 'The migration folder does not exist at '.$path.".  Creating...\n";

					if (!IOHelper::createFolder($path))
					{
						echo 'Sorry... I tried to create the folder, but could not.';
						return 1;
					}
					else
					{
						echo 'Successfully created.';
					}
				}
			}
		}

		$yiiVersion = craft()->getYiiVersion();
		echo "\nCraft CMS Migration Tool (based on Yii v{$yiiVersion})\n\n";

		return true;
	}

	/**
	 * Used for stopping "down" actions from occurring.
	 *
	 * @param array $args The arguments for the action.
	 *
	 * @return null
	 */
	public function actionDown($args)
	{
		die("Down migrations are not supported.\n");
	}

	/**
	 * Used for stopping "redo" actions from occurring.
	 *
	 * @param array $args
	 *
	 * @return null
	 */
	public function actionRedo($args)
	{
		die("Redo is not supported.\n");
	}

	/**
	 * Used for stopping "To" actions from occurring.
	 *
	 * @param array $args
	 *
	 * @return null
	 */
	public function actionTo($args)
	{
		die("To is not supported.\n");
	}

	/**
	 * Used for stopping "Mark" actions from occurring.
	 *
	 * @param array $args
	 *
	 * @return null
	 */
	public function actionMark($args)
	{
		die("Mark is not supported.\n");
	}

	/**
	 * Used for creating a new migration, for either Craft or a plugin.
	 *
	 *    yiic migrate create MigrationDescription PluginHandle
	 *
	 * If PluginHandle is omitted, the migration is created for Craft in craft/app/migrations. If it is available, the
	 * migration is created in craft/plugins/PluginHandle/migrations.
	 *
	 * The migration description can only contain letters, digits and/or underscore characters.
	 *
	 * @param array $args The arguments passed in.
	 *
	 * @return int
	 */
	public function actionCreate($args)
	{
		$pluginHandle = false;
		if (isset($args[1]))
		{
			// See if this is a plugin
			$plugin = craft()->plugins->getPlugin($args[1]);
			if ($plugin)
			{
				$name = $args[0];
				$pluginHandle = $args[1];
			}
			else
			{
				$name = $args[1];
			}

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

		if ($pluginHandle)
		{
			$this->_validatePlugin($pluginHandle);

			if (!preg_match('/^\w+$/', $pluginHandle))
			{
				echo "Error: The name of the plugin must contain letters, digits and/or underscore characters only.\n";
				return 1;
			}

			$fullName = 'm'.gmdate('ymd_His').'_'.StringHelper::toLowerCase($pluginHandle).'_'.$name;
			$migrationNameDesc = 'mYYMMDD_HHMMSS_pluginHandle_migrationName';

			// The plugin path should always be the plugin's migration directory.
			$path = craft()->path->getMigrationsPath($pluginHandle);
		}
		else
		{
			// The plugin path for Craft can vary.
			$path = rtrim(IOHelper::normalizePathSeparators($args[0]), '/').'/';
		}

		$content = strtr(craft()->migrations->getTemplate(), array('{ClassName}' => $fullName, '{MigrationNameDesc}' => $migrationNameDesc));
		$file = $path.$fullName.'.php';

		if ($this->confirm("Create new migration '$file'?"))
		{
			IOHelper::writeToFile($file, $content);
			echo "New migration created successfully.\n";
		}
	}

	/**
	 * Used for running any new migrations for either Craft or a plugin.
	 *
	 *     yiic migrate up PluginHandle
	 *
	 * If PluginHandle is omitted, any new migrations that haven't ran yet in craft/app/migrations will be ran. If it is
	 * available, any new migrations in craft/plugins/PluginHandle/migrations that haven't ran yet, will run.
	 *
	 * @param array $args The arguments passed in.
	 *
	 * @return int
	 */
	public function actionUp($args)
	{
		if (isset($args[0]))
		{
			$plugin = $this->_validatePlugin($args[0]);
			if (craft()->migrations->runToTop($plugin))
			{
				echo "Migrated ".$plugin->getClassHandle()." to top successfully.\n";
				return 0;
			}
			else
			{
				echo "There was a problem migrating ".$plugin->getClassHandle()." to top.  Check the logs.\n";
			}

			return 1;
		}

		if (craft()->migrations->runToTop())
		{
			echo "Migrated Craft CMS to top successfully.\n";
			return 0;
		}
		else
		{
			echo "There was a problem migrating Craft CMS to top. Check the logs.\n";
		}

		return 1;
	}

	/**
	 * Used for seeing which migrations haven't already been ran in Craft or a plugin.
	 *
	 *     yiic migrate history PluginHandle
	 *
	 * If PluginHandle is omitted, it will display all of Craft's migrations that have already ran. If it is available,
	 * it will display all of PluginHandle's migrations that have already ran.
	 *
	 * @param array $args The arguments passed in.
	 *
	 * @return int
	 */
	public function actionHistory($args)
	{
		$plugin = null;

		if (isset($args[0]))
		{
			if ($args[0] !== 'all')
			{
				$plugin = $this->_validatePlugin($args[0]);
			}
			else
			{
				$plugin = $args[0];
			}

		}

		$migrations = $this->getMigrationHistory($plugin);

		if ($migrations === array())
		{
			if ($plugin === 'all')
			{
				echo "No migration has been ran for Craft CMS or any plugins.\n";
			}
			else if ($plugin)
			{
				echo "No migration has been ran for ".$plugin->getClassHandle()."\n";
			}
			else
			{
				echo "No migration has been done before.\n";
			}

		}
		else
		{
			$n = count($migrations);

			if ($plugin === 'all')
			{
				echo "A total of $n ".($n === 1 ? 'migration has' : 'migrations have')." been applied before for Craft CMS and all plugins:\n";
			}
			else if ($plugin)
			{
				echo "A total of $n ".($n === 1 ? 'migration has' : 'migrations have')." been applied before for ".$plugin->getClassHandle().":\n";
			}
			else
			{
				echo "A total of $n ".($n === 1 ? 'migration has' : 'migrations have')." been applied before:\n";
			}

			foreach ($migrations as $version => $time)
			{
				echo "    (".date('Y-m-d H:i:s',$time).') '.$version."\n";
			}
		}
	}

	/**
	 * Gets the migration template used for generating new migrations.
	 *
	 * Overriding Yii's implementation with Craft specific logic.
	 *
	 * @return string
	 */
	public function getTemplate()
	{
		return craft()->migrations->getTemplate();
	}

	/**
	 * Used for seeing any new migrations that haven't run yet in Craft or a plugin.
	 *
	 *     yiic migrate new PluginHandle
	 *
	 * If PluginHandle is omitted, it will display any new Craft migrations that have not run, yet. If it is available,
	 * it will display all of PluginHandle's migrations that have not run, yet.
	 *
	 * @param array $args The arguments passed in.
	 *
	 * @return int
	 */
	public function actionNew($args)
	{
		$plugin = null;

		if (isset($args[0]))
		{
			$plugin = $this->_validatePlugin($args[0]);
		}

		$migrations = $this->getNewMigrations($plugin);

		if ($migrations === array())
		{
			if ($plugin)
			{
				echo "No new migrations found for ".$plugin->getClassHandle().". The plugin is up-to-date.\n";
			}
			else
			{
				echo "No new migrations found. Craft CMS is up-to-date.\n";
			}

		}
		else
		{
			$n = count($migrations);

			if ($plugin)
			{
				echo "Found $n new ".($n === 1 ? 'migration' : 'migrations')." for ".$plugin->getClassHandle().":\n";
			}
			else
			{
				echo "Found $n new ".($n === 1 ? 'migration' : 'migrations').":\n";
			}

			foreach ($migrations as $migration)
			{
				echo "    ".$migration."\n";
			}
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Gets the migration history for either Craft or a plugin.
	 *
	 * Overriding Yii's implementation with Craft specific logic.
	 *
	 * @param BasePlugin|null $plugin If null, will get Craft's migration history. If a plugin instance, will get the
	 *                                plugin's migration history.
	 *
	 * @return mixed
	 */
	protected function getMigrationHistory($plugin = null)
	{
		$migrations = craft()->migrations->getMigrationHistory($plugin);

		// Convert the dates to Unix timestamps
		foreach ($migrations as &$migration)
		{
			$migration['applyTime'] = $migration['applyTime']->getTimestamp();
		}

		return HtmlHelper::listData($migrations, 'version', 'applyTime');
	}

	/**
	 * Gets any new migrations for either Craft or a plugin.
	 *
	 * Overriding Yii's implementation with Craft specific logic.
	 *
	 * @param BasePlugin|null $plugin If null, will get any new Craft migrations. If a plugin instance, will get new
	 *                                plugin migrations.
	 *
	 * @return array
	 */
	protected function getNewMigrations($plugin = null)
	{
		return craft()->migrations->getNewMigrations($plugin);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Given a plugin handle, will retrieve it, or display an error if it doesn't exist or is disabled.
	 *
	 * @param string $pluginHandle The handle of the plugin to search for.
	 *
	 * @return BasePlugin|int
	 */
	private function _validatePlugin($pluginHandle)
	{
		$plugin = craft()->plugins->getPlugin($pluginHandle);

		if (!$plugin)
		{
			echo "Error: Could not find an enabled plugin with the handle {$pluginHandle}.\n";
			die(1);
		}

		return $plugin;
	}
}
