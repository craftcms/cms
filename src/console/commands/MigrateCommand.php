<?php
namespace Blocks;

/**
 *
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
			// If the 2nd dimension is the 2nd index, then we know it's a plugin.  No need to make them specify the path.
			if (isset($params[0][2]))
			{
				$plugin = $params[0][2];

				$path = blx()->path->getMigrationsPath($plugin);

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

		$yiiVersion = Blocks::getYiiVersion();
		echo "\nBlocks Migration Tool (based on Yii v{$yiiVersion})\n\n";

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
		if (isset($args[1]))
		{
			$name = $args[1];

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

		if (isset($args[2]))
		{
			$this->_validatePlugin($args[2]);
			$pluginHandle = $args[2];

			if (!preg_match('/^\w+$/', $pluginHandle))
			{
				echo "Error: The name of the plugin must contain letters, digits and/or underscore characters only.\n";
				return 1;
			}

			$fullName = 'm'.gmdate('ymd_His').'_'.strtolower($pluginHandle).'_'.$name;
			$migrationNameDesc = 'mYYMMDD_HHMMSS_pluginHandle_migrationName';
			$path = blx()->path->getMigrationsPath($pluginHandle);
		}
		else
		{
			$path = blx()->path->getMigrationsPath();
		}

		$content = strtr(blx()->migrations->getTemplate(), array('{ClassName}' => $fullName, '{MigrationNameDesc}' => $migrationNameDesc));
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
			return blx()->migrations->runToTop($plugin);
		}

		return blx()->migrations->runToTop();
	}

	/**
	 * @param $args
	 * @return int|void
	 */
	public function actionHistory($args)
	{
		$limit = isset($args[1]) ? (int)$args[1] : -1;
		$plugin = null;

		if (isset($args[0]))
		{
			$plugin = $this->_validatePlugin($args[0]);
		}

		$migrations = $this->getMigrationHistory(null, $limit);

		if ($migrations === array())
		{
			if ($plugin)
			{
				echo "No migration has been done before for ".$plugin->getClassHandle()."\n";
			}
			else
			{
				echo "No migration has been done before.\n";
			}

		}
		else
		{
			$n = count($migrations);

			if ($limit > 0)
			{
				if ($plugin)
				{
					echo "Showing the last $n applied ".($n === 1 ? 'migration' : 'migrations')." for ".$plugin->getClassHandle().":\n";
				}
				else
				{
					echo "Showing the last $n applied ".($n === 1 ? 'migration' : 'migrations').":\n";
				}
			}
			else
			{
				if ($plugin)
				{
					echo "A total of $n ".($n === 1 ? 'migration has' : 'migrations have')." been applied before for ".$plugin->getClassHandle().":\n";
				}
				else
				{
					echo "A total of $n ".($n === 1 ? 'migration has' : 'migrations have')." been applied before:\n";
				}

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
		$limit = isset($args[1]) ? (int)$args[1] : -1;

		$plugin = null;

		if (isset($args[0]))
		{
			$plugin = $this->_validatePlugin($args[0]);
		}

		$migrations = $this->getNewMigrations($plugin, $limit);

		if ($migrations === array())
		{
			if ($plugin)
			{
				echo "No new migrations found for ".$plugin->getClassHandle().". The plugin is up-to-date.\n";
			}
			else
			{
				echo "No new migrations found. Blocks is up-to-date.\n";
			}

		}
		else
		{
			$n = count($migrations);

			if ($limit > 0 && $n > $limit)
			{
				$migrations = array_slice($migrations, 0, $limit);

				if ($plugin)
				{
					echo "Showing $limit out of $n new ".($n === 1 ? 'migration' : 'migrations')." for ".$plugin->getClassHandle().":\n";
				}
				else
				{
					echo "Showing $limit out of $n new ".($n === 1 ? 'migration' : 'migrations').":\n";
				}
			}
			else
			{
				if ($plugin)
				{
					echo "Found $n new ".($n===1 ? 'migration' : 'migrations')." for ".$plugin->getClassHandle().":\n";
				}
				else
				{
					echo "Found $n new ".($n===1 ? 'migration' : 'migrations').":\n";
				}
			}

			foreach ($migrations as $migration)
			{
				echo "    ".$migration."\n";
			}
		}
	}

	/**
	 * @param null $plugin
	 * @param      $limit
	 * @return mixed
	 */
	protected function getMigrationHistory($plugin = null, $limit = null)
	{
		$migrations = blx()->migrations->getMigrationHistory($plugin, $limit);

		// Convert the dates to Unix timestamps
		foreach ($migrations as &$migration)
		{
			$migration['applyTime'] = $migration['applyTime']->getTimestamp();
		}

		return HtmlHelper::listData($migrations, 'version', 'applyTime');
	}

	/**
	 * @return array
	 */
	protected function getNewMigrations()
	{
		return blx()->migrations->getNewMigrations();
	}

	/**
	 * @return string
	 */
	public function getTemplate()
	{
		return blx()->migrations->getTemplate();
	}

	/**
	 * @param $pluginHandle
	 * @return int
	 */
	private function _validatePlugin($pluginHandle)
	{
		$plugin = blx()->plugins->getPlugin($pluginHandle);

		if (!$plugin)
		{
			echo "Error: Could not find an enabled plugin with the handle {$pluginHandle}.\n";
			return 1;
		}

		return $plugin;
	}
}
