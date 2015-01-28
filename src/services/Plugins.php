<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\BasePlugin;
use craft\app\base\PluginInterface;
use craft\app\dates\DateTime;
use craft\app\db\Query;
use craft\app\errors\Exception;
use craft\app\events\Event;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\StringHelper;
use craft\app\records\Migration as MigrationRecord;
use yii\base\Component;

/**
 * The Plugins service provides APIs for managing plugins.
 *
 * An instance of the Plugins service is globally accessible in Craft via [[Application::plugins `Craft::$app->plugins`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Plugins extends Component
{
	// Constants
	// =========================================================================

	/**
     * @event Event The event that is triggered after all plugins have been loaded.
     */
    const EVENT_AFTER_LOAD_PLUGINS = 'afterLoadPlugins';

	// Properties
	// =========================================================================

	/**
	 * Stores whether plugins have been loaded yet for this request.
	 *
	 * @var bool
	 */
	private $_pluginsLoaded = false;

	/**
	 * Stores whether plugins are in the middle of being loaded.
	 *
	 * @var bool
	 */
	private $_loadingPlugins = false;

	/**
	 * Stores all plugins, whether installed or not.
	 *
	 * @var array
	 */
	private $_plugins = [];

	/**
	 * Stores all plugins, whether installed or not.
	 *
	 * @var array
	 */
	private $_enabledPlugins = [];

	/**
	 * Stores all plugins in the system, regardless of whether they're installed/enabled or not.
	 *
	 * @var array
	 */
	private $_allPlugins;

	/**
	 * Holds a list of all of the stored info for enabled plugins, indexed by the plugins’ class names.
	 *
	 * @var array
	 */
	private $_storedPluginInfo = [];

	// Public Methods
	// =========================================================================

	/**
	 * Returns whether plugins have been loaded yet for this request.
	 *
	 * @return bool Whether plugins have been loaded yet.
	 */
	public function arePluginsLoaded()
	{
		return $this->_pluginsLoaded;
	}

	/**
	 * Loads the enabled plugins.
	 *
	 * @return null
	 */
	public function loadPlugins()
	{
		if (!$this->_pluginsLoaded && !$this->_loadingPlugins)
		{
			if (Craft::$app->isInstalled())
			{
				// Prevent this function from getting called twice.
				$this->_loadingPlugins = true;

				// Find all of the enabled plugins
				$rows = (new Query())
					->select(['id', 'class', 'version', 'settings', 'installDate'])
					->from('plugins')
					->where('enabled=1')
					->all();

				$names = [];

				foreach ($rows as $row)
				{
					$plugin = $this->_getPlugin($row['class']);

					if ($plugin)
					{
						$this->_autoloadPluginClasses($plugin);

						// Clean it up a bit
						$row['settings'] = JsonHelper::decode($row['settings']);
						$row['installDate'] = DateTime::createFromString($row['installDate']);

						$this->_storedPluginInfo[$row['class']] = $row;

						$lcPluginHandle = mb_strtolower($plugin->getClassHandle());
						$this->_plugins[$lcPluginHandle] = $plugin;
						$this->_enabledPlugins[$lcPluginHandle] = $plugin;
						$names[] = $plugin->getName();

						$plugin->setSettings($row['settings']);

						$plugin->isInstalled = true;
						$plugin->isEnabled = true;
					}
				}

				// Sort plugins by name
				$this->_sortPlugins($names, $this->_enabledPlugins);

				// Now that all of the components have been imported, initialize all the plugins
				foreach ($this->_enabledPlugins as $plugin)
				{
					$plugin->init();
				}

				$this->_loadingPlugins = false;
			}

			$this->_pluginsLoaded = true;

			// Fire an 'afterLoadPlugins' event
			$this->trigger(static::EVENT_AFTER_LOAD_PLUGINS, new Event($this));
		}
	}

	/**
	 * Returns a plugin by its handle.
	 *
	 * @param string $handle      The plugin’s handle.
	 * @param bool   $enabledOnly Whether the plugin must be installed and enabled. Defaults to `true`.
	 *
	 * @return BasePlugin|null The plugin.
	 */
	public function getPlugin($handle, $enabledOnly = true)
	{
		$lcPluginHandle = mb_strtolower($handle);

		if ($enabledOnly)
		{
			if (isset($this->_enabledPlugins[$lcPluginHandle]))
			{
				return $this->_enabledPlugins[$lcPluginHandle];
			}
			else
			{
				return null;
			}
		}
		else
		{
			if (!array_key_exists($lcPluginHandle, $this->_plugins))
			{
				// Make sure $handle has the right casing
				$handle = $this->_getPluginHandleFromFileSystem($handle);

				$plugin = $this->_getPlugin($handle);

				if ($plugin)
				{
					// Is it installed (but disabled)?
					$plugin->isInstalled = (new Query())
						->from('plugins')
						->where(['class' => $plugin->getClassHandle()])
						->exists();
				}

				$this->_plugins[$lcPluginHandle] = $plugin;
			}

			return $this->_plugins[$lcPluginHandle];
		}
	}

	/**
	 * Returns all the plugins.
	 *
	 * @param bool $enabledOnly Whether to only return plugins that are installed and enabled. Defaults to `true`.
	 *
	 * @return BasePlugin[] The plugins.
	 */
	public function getPlugins($enabledOnly = true)
	{
		if ($enabledOnly)
		{
			return $this->_enabledPlugins;
		}
		else
		{
			if (!isset($this->_allPlugins))
			{
				$this->_allPlugins = [];

				// Find all of the plugins in the plugins folder
				$pluginsPath = Craft::$app->path->getPluginsPath();
				$pluginFolderContents = IOHelper::getFolderContents($pluginsPath, false);

				if ($pluginFolderContents)
				{
					foreach ($pluginFolderContents as $pluginFolderContent)
					{
						// Make sure it's actually a folder.
						if (IOHelper::folderExists($pluginFolderContent))
						{
							$pluginFolderContent = IOHelper::normalizePathSeparators($pluginFolderContent);
							$pluginFolderName = mb_strtolower(IOHelper::getFolderName($pluginFolderContent, false));
							$pluginFilePath = IOHelper::getFolderContents($pluginFolderContent, false, ".*Plugin\.php");

							if (is_array($pluginFilePath) && count($pluginFilePath) > 0)
							{
								$pluginFileName = IOHelper::getFileName($pluginFilePath[0], false);

								// Chop off the "Plugin" suffix
								$handle = mb_substr($pluginFileName, 0, StringHelper::length($pluginFileName) - 6);
								$lcHandle = mb_strtolower($handle);

								// Validate that the lowercase plugin class handle is the same as the folder name
								// and that we haven't already loaded a plugin with the same handle but different casing
								if ($lcHandle === $pluginFolderName && !isset($this->_allPlugins[$lcHandle]))
								{
									$plugin = $this->getPlugin($handle, false);

									if ($plugin)
									{
										$this->_allPlugins[$lcHandle] = $plugin;
										$names[] = $plugin->getName();
									}
								}
							}
						}
					}

					if (!empty($names))
					{
						// Sort plugins by name
						$this->_sortPlugins($names, $this->_allPlugins);
					}
				}
			}

			return $this->_allPlugins;
		}
	}

	/**
	 * Enables a plugin by its handle.
	 *
	 * @param string $handle The plugin’s handle.
	 *
	 * @throws Exception
	 * @return bool Whether the plugin was enabled successfully.
	 */
	public function enablePlugin($handle)
	{
		$plugin = $this->getPlugin($handle, false);

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (!$plugin->isInstalled)
		{
			throw new Exception(Craft::t('app', '“{plugin}” can’t be enabled because it isn’t installed yet.', ['plugin' => $plugin->getName()]));
		}

		if ($plugin->isEnabled)
		{
			// Done!
			return true;
		}

		$lcPluginHandle = mb_strtolower($plugin->getClassHandle());

		Craft::$app->getDb()->createCommand()->update('plugins',
			['enabled' => 1],
			['class' => $plugin->getClassHandle()]
		)->execute();

		$plugin->isEnabled = true;
		$this->_enabledPlugins[$lcPluginHandle] = $plugin;

		return true;
	}

	/**
	 * Disables a plugin by its handle.
	 *
	 * @param string $handle The plugin’s handle.
	 *
	 * @throws Exception
	 * @return bool Whether the plugin was disabled successfully.
	 */
	public function disablePlugin($handle)
	{
		$plugin = $this->getPlugin($handle, false);

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (!$plugin->isInstalled)
		{
			throw new Exception(Craft::t('app', '“{plugin}” can’t be disabled because it isn’t installed yet.', ['plugin' => $plugin->getName()]));
		}

		if (!$plugin->isEnabled)
		{
			// Done!
			return true;
		}

		$lcPluginHandle = mb_strtolower($plugin->getClassHandle());

		Craft::$app->getDb()->createCommand()->update('plugins',
			['enabled' => 0],
			['class' => $plugin->getClassHandle()]
		)->execute();

		$plugin->isEnabled = false;
		unset($this->_enabledPlugins[$lcPluginHandle]);

		return true;
	}

	/**
	 * Installs a plugin by its handle.
	 *
	 * @param string $handle The plugin’s handle.
	 *
	 * @throws Exception|\Exception
	 * @return bool Whether the plugin was installed successfully.
	 */
	public function installPlugin($handle)
	{
		$plugin = $this->getPlugin($handle, false);

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if ($plugin->isInstalled)
		{
			// Done!
			return true;
		}

		$lcPluginHandle = mb_strtolower($plugin->getClassHandle());

		$plugin->onBeforeInstall();

		$transaction = Craft::$app->getDb()->getCurrentTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
		try
		{
			// Add the plugins as a record to the database.
			Craft::$app->getDb()->createCommand()->insert('plugins', [
				'class'       => $plugin->getClassHandle(),
				'version'     => $plugin->version,
				'enabled'     => true,
				'installDate' => DateTimeHelper::currentTimeForDb(),
			])->execute();

			$plugin->isInstalled = true;
			$plugin->isEnabled = true;
			$this->_enabledPlugins[$lcPluginHandle] = $plugin;

			$this->_savePluginMigrations(Craft::$app->getDb()->getLastInsertID(), $plugin->getClassHandle());
			$this->_autoloadPluginClasses($plugin);
			$plugin->createTables();

			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		$plugin->onAfterInstall();

		return true;
	}

	/**
	 * Uninstalls a plugin by its handle.
	 *
	 * @param string $handle The plugin’s handle.
	 *
	 * @throws Exception|\Exception
	 * @return bool Whether the plugin was uninstalled successfully.
	 */
	public function uninstallPlugin($handle)
	{
		$plugin = $this->getPlugin($handle, false);

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (!$plugin->isInstalled)
		{
			// Done!
			return true;
		}

		$lcPluginHandle = mb_strtolower($plugin->getClassHandle());

		if (!$plugin->isEnabled)
		{
			// Pretend that the plugin is enabled just for this request
			$plugin->isEnabled = true;
			$this->_enabledPlugins[$lcPluginHandle] = $plugin;
			$this->_autoloadPluginClasses($plugin);

			$pluginRow = (new Query())
				->select('id')
				->from('plugins')
				->where('class=:class', ['class' => $plugin->getClassHandle()])
				->one();

			$pluginId = $pluginRow['id'];
		}
		else
		{
			$pluginId = $this->_storedPluginInfo[$handle]['id'];
		}

		$transaction = Craft::$app->getDb()->getCurrentTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
		try
		{
			$plugin->onBeforeUninstall();

			// If the plugin has any element types, delete their elements
			//$elementTypeInfo = Craft::$app->components->types['element'];
			//$elementTypeClasses = $this->getPluginClasses($plugin, $elementTypeInfo['subfolder'], $elementTypeInfo['suffix']);

			//foreach ($elementTypeClasses as $class)
			//{
			//	$elementType = Craft::$app->components->initializeComponent($class, $elementTypeInfo['instanceof']);

			//	if ($elementType)
			//	{
			//		Craft::$app->elements->deleteElementsByType($elementType->getClassHandle());
			//	}
			//}

			// Drop any tables created by the plugin's records
			$plugin->dropTables();

			// Remove the row from the database.
			Craft::$app->getDb()->createCommand()->delete('plugins', ['class' => $handle])->execute();

			// Remove any migrations.
			Craft::$app->getDb()->createCommand()->delete('migrations', ['pluginId' => $pluginId])->execute();

			if ($transaction !== null)
			{
				// Let's commit to this.
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		$plugin->isEnabled = false;
		$plugin->isInstalled = false;
		unset($this->_enabledPlugins[$lcPluginHandle]);
		unset($this->_plugins[$lcPluginHandle]);
		unset($this->_storedPluginInfo[$handle]);

		return true;
	}

	/**
	 * Saves a plugin's settings.
	 *
	 * @param BasePlugin $plugin   The plugin.
	 * @param array      $settings The plugin’s new settings.
	 *
	 * @return bool Whether the plugin’s settings were saved successfully.
	 */
	public function savePluginSettings(BasePlugin $plugin, $settings)
	{
		// Give the plugin a chance to prep the settings from post
		$preppedSettings = $plugin->prepSettings($settings);

		// Set the prepped settings on the plugin
		$plugin->setSettings($preppedSettings);

		// Validate them, now that it's a model
		if ($plugin->getSettings()->validate())
		{
			// JSON-encode them and save the plugin row
			$settings = JsonHelper::encode($plugin->getSettings()->getAttributes());

			$affectedRows = Craft::$app->getDb()->createCommand()->update('plugins', [
				'settings' => $settings
			], [
				'class' => $plugin->getClassHandle()
			])->execute();

			return (bool) $affectedRows;
		}
	}

	/**
	 * Calls a method on all plugins that have it, and returns an array of the results, indexed by plugin handles.
	 *
	 * @param string $method     The name of the method.
	 * @param array  $args       Any arguments that should be passed when calling the method on the plugins.
	 * @param bool   $ignoreNull Whether plugins that have the method but return a null response should be ignored. Defaults to false.
	 *
	 * @return array An array of the plugins’ responses.
	 */
	public function call($method, $args = [], $ignoreNull = false)
	{
		$allResults = [];

		foreach ($this->getPlugins() as $plugin)
		{
			if (method_exists($plugin, $method))
			{
				$result = call_user_func_array([$plugin, $method], $args);

				if (!$ignoreNull || $result !== null)
				{
					$allResults[$plugin->getClassHandle()] = $result;
					unset($result);
				}
			}
		}

		return $allResults;
	}

	/**
	 * Calls a method on the first plugin that has it, and returns the result.
	 *
	 * @param string $method     The name of the method.
	 * @param array  $args       Any arguments that should be passed when calling the method on the plugins.
	 * @param bool   $ignoreNull Whether plugins that have the method but return a null response should be ignored. Defaults to false.
	 *
	 * @return mixed The plugin’s response, or null.
	 */
	public function callFirst($method, $args = [], $ignoreNull = false)
	{
		foreach ($this->getPlugins() as $plugin)
		{
			if (method_exists($plugin, $method))
			{
				$result = call_user_func_array([$plugin, $method], $args);

				if (!$ignoreNull || $result !== null)
				{
					return $result;
				}
			}
		}
	}

	/**
	 * Returns whether the given plugin’s local version number is greater than the record we have in the database.
	 *
	 * @param BasePlugin $plugin The plugin.
	 *
	 * @return bool Whether the plugin’s local version number is greater than the record we have in the database.
	 */
	public function doesPluginRequireDatabaseUpdate(BasePlugin $plugin)
	{
		$storedPluginInfo = $this->getStoredPluginInfo($plugin);

		if ($storedPluginInfo)
		{
			if (version_compare($plugin->getVersion(), $storedPluginInfo['version'], '>'))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the stored info for a given plugin.
	 *
	 * @param BasePlugin $plugin The plugin.
	 *
	 * @return array|null The stored info, if there is any.
	 */
	public function getStoredPluginInfo(BasePlugin $plugin)
	{
		if (isset($this->_storedPluginInfo[$plugin->getClassHandle()]))
		{
			return $this->_storedPluginInfo[$plugin->getClassHandle()];
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Throws a "no plugin exists" exception.
	 *
	 * @param string $handle
	 *
	 * @throws Exception
	 * @return null
	 */
	private function _noPluginExists($handle)
	{
		throw new Exception(Craft::t('app', 'No plugin exists with the class “{class}”', ['class' => $handle]));
	}

	/**
	 * Adds autoloading support for \craft\plugins\pluginname\* classes.
	 *
	 * @param BasePlugin $plugin
	 */
	private function _autoloadPluginClasses(BasePlugin $plugin)
	{
		$handle = mb_strtolower($plugin->getClassHandle());
		Craft::setAlias('@craft/plugins/'.$handle, '@plugins/'.$handle);
	}

	/**
	 * If the plugin already had a migrations folder with migrations in it, let's save them in the db.
	 *
	 * @param int    $pluginId
	 * @param string $pluginHandle
	 *
	 * @throws Exception
	 */
	private function _savePluginMigrations($pluginId, $pluginHandle)
	{
		$migrationsFolder = Craft::$app->path->getPluginsPath().'/'.mb_strtolower($pluginHandle).'/migrations/';

		if (IOHelper::folderExists($migrationsFolder))
		{
			$migrations = [];
			$migrationFiles = IOHelper::getFolderContents($migrationsFolder, false, "(m(\d{6}_\d{6})_.*?)\.php");

			if ($migrationFiles)
			{
				foreach ($migrationFiles as $file)
				{
					if (IOHelper::fileExists($file))
					{
						$migration = new MigrationRecord();
						$migration->version = IOHelper::getFileName($file, false);
						$migration->applyTime = DateTimeHelper::currentUTCDateTime();
						$migration->pluginId = $pluginId;

						$migrations[] = $migration;
					}
				}

				foreach ($migrations as $migration)
				{
					if (!$migration->save())
					{
						throw new Exception(Craft::t('app', 'There was a problem saving to the migrations table: ').$this->_getFlattenedErrors($migration->getErrors()));
					}
				}
			}
		}
	}

	/**
	 * Returns a new plugin instance based on its class handle.
	 *
	 * @param string $handle
	 *
	 * @return BasePlugin|null
	 */
	private function _getPlugin($handle)
	{
		// Get the full class name
		$lcHandle = mb_strtolower($handle);
		$class = '\\craft\\plugins\\'.$lcHandle.'\\Plugin';

		// Skip the autoloader
		if (!class_exists($class, false))
		{
			$path = Craft::$app->path->getPluginsPath().$lcHandle.'/Plugin.php';

			if (($path = IOHelper::fileExists($path, false)) !== false)
			{
				require $path;

				if (!class_exists($class, false))
				{
					return null;
				}
			}
			else
			{
				return;
			}
		}

		$plugin = new $class;

		// Make sure the plugin implements the PluginInterface
		if (!$plugin instanceof PluginInterface)
		{
			return null;
		}

		return $plugin;
	}

	/**
	 * Returns the actual plugin class handle based on a case-insensitive handle.
	 *
	 * @param string $iHandle
	 *
	 * @return bool|string
	 */
	private function _getPluginHandleFromFileSystem($iHandle)
	{
		$pluginsPath = Craft::$app->path->getPluginsPath();
		$fullPath = $pluginsPath.'/'.mb_strtolower($iHandle).'/'.$iHandle.'Plugin.php';

		if (($file = IOHelper::fileExists($fullPath, true)) !== false)
		{
			$file = IOHelper::getFileName($file, false);
			return mb_substr($file, 0, mb_strlen($file) - strlen('Plugin'));
		}

		return false;
	}

	/**
	 * Get a flattened list of model errors
	 *
	 * @param array $errors
	 *
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

	/**
	 * @param $names
	 * @param $secondaryArray
	 *
	 * @return null
	 */
	private function _sortPlugins(&$names, &$secondaryArray)
	{
		// Sort plugins by name
		array_multisort($names, SORT_NATURAL | SORT_FLAG_CASE, $secondaryArray);
	}
}
