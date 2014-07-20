<?php
namespace Craft;

/**
 * Class MigrateCommand
 *
 * @package craft.app.consolecommands
 */
class MigrateCommand extends \MigrateCommand
{
	/**
	 * @param string $action
	 * @param array  $params
	 * @return bool
	 */
	public function beforeAction($action, $params)
	{
		if ($action == 'create')
		{
			// If the 1nd dimension is the 1nd index, then we know it's a plugin.  No need to make them specify the path.
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
		echo "\n@@@appName@@@ Migration Tool (based on Yii v{$yiiVersion})\n\n";

		return true;
	}

	/**
	 * @param $args
	 * @return void
	 */
	public function actionDown($args)
	{
		die("Down migrations are not supported.\n");
	}

	/**
	 * @param $args
	 * @return int|void
	 */
	public function actionRedo($args)
	{
		die("Redo is not supported.\n");
	}

	/**
	 * @param $args
	 * @return int|void
	 */
	public function actionTo($args)
	{
		die("To is not supported.\n");
	}

	/**
	 * @param $args
	 * @return int|void
	 */
	public function actionMark($args)
	{
		die("Mark is not supported.\n");
	}

	/**
	 * @param $args
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
	 * @param $args
	 * @return int|void
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
				return 1;
			}
		}

		if (craft()->migrations->runToTop())
		{
			echo "Migrated @@@appName@@@ to top successfully.\n";
			return 0;
		}
		else
		{
			echo "There was a problem migrating @@@appName@@@ to top. Check the logs.\n";
			return 1;
		}
	}

	/**
	 * @param $args
	 * @return int|void
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
				echo "No migration has been ran for @@@appName@@@ or any plugins.\n";
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
				echo "A total of $n ".($n === 1 ? 'migration has' : 'migrations have')." been applied before for @@@appName@@@ and all plugins:\n";
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
	 * @param $args
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
				echo "No new migrations found. @@@appName@@@ is up-to-date.\n";
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

	/**
	 * @param null $plugin
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
	 * @param null $plugin
	 * @return array
	 */
	protected function getNewMigrations($plugin = null)
	{
		return craft()->migrations->getNewMigrations($plugin);
	}

	/**
	 * @return string
	 */
	public function getTemplate()
	{
		return craft()->migrations->getTemplate();
	}

	/**
	 * @param $pluginHandle
	 * @return int
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
