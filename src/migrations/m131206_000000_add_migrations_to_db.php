<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m131206_000000_add_migrations_to_db extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->_addMissingCraftMigrations();
		$this->_addMissingPluginMigrations();
		return true;
	}

	/**
	 * @throws Exception
	 */
	private function _addMissingCraftMigrations()
	{
		$migrations = array();

		$migrationsFolder = craft()->path->getAppPath().'migrations/';
		$migrationFiles = IOHelper::getFolderContents($migrationsFolder, false, "(m(\d{6}_\d{6})_.*?)\.php");

		if ($migrationFiles)
		{
			Craft::log('Found '.count($migrationFiles).' migrations for Craft.', LogLevel::Info, true);
			foreach ($migrationFiles as $file)
			{
				if (IOHelper::fileExists($file))
				{
					$migrationName = IOHelper::getFileName($file, false);

					// Skip this one and the next one.
					if ($migrationName == 'm131206_000000_add_migrations_to_db' || $migrationName == 'm131209_000000_remove_recent_transform_indexes')
					{
						continue;
					}

					$exists = (bool)craft()->db->createCommand()
						->select('*')
						->from('migrations')
						->where('version=:name AND pluginId is NULL', array('name' => $migrationName))
						->queryScalar();

					if (!$exists)
					{
						Craft::log('Migration '.$migrationName.' for Craft is not in the database.  Adding to the list.', LogLevel::Info, true);

						$migration = new MigrationRecord();
						$migration->version = $migrationName;
						$migration->applyTime = DateTimeHelper::currentUTCDateTime();

						$migrations[] = $migration;
					}
					else
					{
						Craft::log('Migration '.$migrationName.' for Craft is already in the database.', LogLevel::Info, true);
					}
				}
			}

			foreach ($migrations as $migration)
			{
				if (!$migration->save())
				{
					Craft::log('Could not populate the migration table.', LogLevel::Error);
					throw new Exception(Craft::t('There was a problem saving to the migrations table:').$this->_getFlattenedErrors($migration->getErrors()));
				}
			}
		}

		Craft::log('Migration table populated successfully for Craft.', LogLevel::Info, true);
	}

	/**
	 * @throws Exception
	 */
	private function _addMissingPluginMigrations()
	{
		// Make sure plugins are loaded.
		craft()->plugins->loadPlugins();

		// Get the enabled plugins.
		$plugins = craft()->plugins->getPlugins();

		Craft::log('Found '.count($plugins).' enabled plugins.', LogLevel::Info, true);
		foreach ($plugins as $plugin)
		{
			$pluginHandle = $plugin->getClassHandle();
			$pluginInfo = craft()->plugins->getPluginInfo($plugin);

			$migrationsFolder = craft()->path->getPluginsPath().strtolower($pluginHandle).'/migrations/';

			if (IOHelper::folderExists($migrationsFolder))
			{
				Craft::log('Plugin '.$pluginHandle.' has a migrations folder.', LogLevel::Info, true);

				$migrations = array();
				$migrationFiles = IOHelper::getFolderContents($migrationsFolder, false, "(m(\d{6}_\d{6})_.*?)\.php");

				if ($migrationFiles)
				{
					Craft::log('Found '.count($migrationFiles).' migrations for plugin '.$pluginHandle.'.', LogLevel::Info, true);
					foreach ($migrationFiles as $file)
					{
						if (IOHelper::fileExists($file))
						{
							$migrationName = IOHelper::getFileName($file, false);

							$exists = (bool)craft()->db->createCommand()
								->select('*')
								->from('migrations')
								->where('version=:name AND pluginId=:pluginId', array('name' => $migrationName, 'pluginId' => $pluginInfo['id']))
								->queryScalar();

							if (!$exists)
							{
								Craft::log('Migration '.$migrationName.' for plugin '.$pluginHandle.' is not in the database.  Adding to the list.', LogLevel::Info, true);

								$migration = new MigrationRecord();
								$migration->version = $migrationName;
								$migration->applyTime = DateTimeHelper::currentUTCDateTime();
								$migration->pluginId = $pluginInfo['id'];

								$migrations[] = $migration;
							}
							else
							{
								Craft::log('Migration '.$migrationName.' for plugin '.$pluginHandle.' is already in the database.', LogLevel::Info, true);
							}
						}
					}

					foreach ($migrations as $migration)
					{
						if (!$migration->save())
						{
							Craft::log('Could not populate the migration table.', LogLevel::Error);
							throw new Exception(Craft::t('There was a problem saving to the migrations table: ').$this->_getFlattenedErrors($migration->getErrors()));
						}
						else
						{
							Craft::log('Successfully added migration '.$migrationName.' for plugin '.$pluginHandle.' to the database', LogLevel::Info, true);
						}
					}
				}

				Craft::log('Migration table populated successfully for plugins.', LogLevel::Info, true);
			}
		}
	}

	/**
	 * @param $errors
	 * @return string
	 */
	private function _getFlattenedErrors($errors)
	{
		$return = '';

		foreach ($errors as $attribute => $attributeErrors)
		{
			$return .= "\n - ".implode("\n - ", $attributeErrors);
		}

		return $return;
	}
}
