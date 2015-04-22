<?php
namespace Craft;

/**
 * PluginsService provides APIs for managing plugins.
 *
 * An instance of PluginsService is globally accessible in Craft via {@link WebApp::plugins `craft()->plugins`}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class PluginsService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * The type of components plugins can have. Defined in app/etc/config/common.php.
	 *
	 * @var array
	 */
	public $autoloadClasses;

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
	private $_plugins = array();

	/**
	 * Stores all plugins, whether installed or not.
	 *
	 * @var array
	 */
	private $_enabledPlugins = array();

	/**
	 * Stores all plugins in the system, regardless of whether they're installed/enabled or not.
	 *
	 * @var array
	 */
	private $_allPlugins;

	/**
	 * Holds a list of all of the enabled plugin info indexed by the plugin class name.
	 *
	 * @var array
	 */
	private $_enabledPluginInfo = array();

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
			if (craft()->isInstalled())
			{
				// Prevent this function from getting called twice.
				$this->_loadingPlugins = true;

				// Find all of the enabled plugins
				$rows = craft()->db->createCommand()
					->select('id, class, version, settings, installDate')
					->from('plugins')
					->where('enabled=1')
					->queryAll();

				$names = array();

				foreach ($rows as $row)
				{
					$plugin = $this->_getPlugin($row['class']);

					if ($plugin)
					{
						$this->_autoloadPluginClasses($plugin);

						// Clean it up a bit
						$row['settings'] = JsonHelper::decode($row['settings']);
						$row['installDate'] = DateTime::createFromString($row['installDate']);

						$this->_enabledPluginInfo[$row['class']] = $row;

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

			// Fire an 'onLoadPlugins' event
			$this->onLoadPlugins(new Event($this));
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
					$plugin->isInstalled = (bool) craft()->db->createCommand()
						->select('count(id)')
						->from('plugins')
						->where(array('class' => $plugin->getClassHandle()))
						->queryScalar();
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
				$this->_allPlugins = array();

				// Find all of the plugins in the plugins folder
				$pluginsPath = craft()->path->getPluginsPath();
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
								$handle = mb_substr($pluginFileName, 0, mb_strlen($pluginFileName) - 6);
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
			throw new Exception(Craft::t('“{plugin}” can’t be enabled because it isn’t installed yet.', array('plugin' => $plugin->getName())));
		}

		if ($plugin->isEnabled)
		{
			// Done!
			return true;
		}

		$lcPluginHandle = mb_strtolower($plugin->getClassHandle());

		craft()->db->createCommand()->update('plugins',
			array('enabled' => 1),
			array('class' => $plugin->getClassHandle())
		);

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
			throw new Exception(Craft::t('“{plugin}” can’t be disabled because it isn’t installed yet.', array('plugin' => $plugin->getName())));
		}

		if (!$plugin->isEnabled)
		{
			// Done!
			return true;
		}

		$lcPluginHandle = mb_strtolower($plugin->getClassHandle());

		craft()->db->createCommand()->update('plugins',
			array('enabled' => 0),
			array('class' => $plugin->getClassHandle())
		);

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

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Add the plugins as a record to the database.
			craft()->db->createCommand()->insert('plugins', array(
				'class'       => $plugin->getClassHandle(),
				'version'     => $plugin->version,
				'enabled'     => true,
				'installDate' => DateTimeHelper::currentTimeForDb(),
			));

			$plugin->isInstalled = true;
			$plugin->isEnabled = true;
			$this->_enabledPlugins[$lcPluginHandle] = $plugin;

			$this->_savePluginMigrations(craft()->db->getLastInsertID(), $plugin->getClassHandle());
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

			$pluginRow = craft()->db->createCommand()
				->select('id')
				->from('plugins')
				->where('class=:class', array('class' => $plugin->getClassHandle()))
				->queryRow();

			$pluginId = $pluginRow['id'];
		}
		else
		{
			$pluginId = $this->_enabledPluginInfo[$handle]['id'];
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			$plugin->onBeforeUninstall();

			// If the plugin has any element types, delete their elements
			$elementTypeInfo = craft()->components->types['element'];
			$elementTypeClasses = $this->getPluginClasses($plugin, $elementTypeInfo['subfolder'], $elementTypeInfo['suffix']);

			foreach ($elementTypeClasses as $class)
			{
				$elementType = craft()->components->initializeComponent($class, $elementTypeInfo['instanceof']);

				if ($elementType)
				{
					craft()->elements->deleteElementsByType($elementType->getClassHandle());
				}
			}

			// Drop any tables created by the plugin's records
			$plugin->dropTables();

			// Remove the row from the database.
			craft()->db->createCommand()->delete('plugins', array('class' => $handle));

			// Remove any migrations.
			craft()->db->createCommand()->delete('migrations', array('pluginId' => $pluginId));

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
		unset($this->_enabledPluginInfo[$handle]);

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

			$affectedRows = craft()->db->createCommand()->update('plugins', array(
				'settings' => $settings
			), array(
				'class' => $plugin->getClassHandle()
			));

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
	public function call($method, $args = array(), $ignoreNull = false)
	{
		$allResults = array();
		$altMethod = 'hook'.ucfirst($method);

		foreach ($this->getPlugins() as $plugin)
		{
			if (method_exists($plugin, $method))
			{
				$result = call_user_func_array(array($plugin, $method), $args);
			}
			else if (method_exists($plugin, $altMethod))
			{
				craft()->deprecator->log('PluginsService::method_hook_prefix', 'The “hook” prefix on the '.get_class($plugin).'::'.$altMethod.'() method name has been deprecated. It should be renamed to '.$method.'().');
				$result = call_user_func_array(array($plugin, $altMethod), $args);
			}

			if (isset($result) && (!$ignoreNull || $result !== null))
			{
				$allResults[$plugin->getClassHandle()] = $result;
				unset($result);
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
	public function callFirst($method, $args = array(), $ignoreNull = false)
	{
		$altMethod = 'hook'.ucfirst($method);

		foreach ($this->getPlugins() as $plugin)
		{
			if (method_exists($plugin, $method))
			{
				$result = call_user_func_array(array($plugin, $method), $args);
			}
			else if (method_exists($plugin, $altMethod))
			{
				craft()->deprecator->log('PluginsService::method_hook_prefix', 'The “hook” prefix on the '.get_class($plugin).'::'.$altMethod.'() method name has been deprecated. It should be renamed to '.$method.'().');
				$result = call_user_func_array(array($plugin, $altMethod), $args);
			}

			if (isset($result) && (!$ignoreNull || $result !== null))
			{
				return $result;
			}
		}
	}

	/**
	 * Calls a method on all plugins that have the method.
	 *
	 * @param string $method The name of the method.
	 * @param array  $args   Any arguments that should be passed when calling the method on the plugins.
	 *
	 * @deprecated Deprecated in 1.0.  Use {@link call()} instead.
	 * @return array An array of the plugins’ responses.
	 */
	public function callHook($method, $args = array())
	{
		craft()->deprecator->log('PluginsService::callHook()', 'PluginsService::callHook() has been deprecated. Use call() instead.');
		return $this->call($method, $args);
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
		$storedPluginInfo = $this->getPluginInfo($plugin);

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
	public function getPluginInfo(BasePlugin $plugin)
	{
		if (isset($this->_enabledPluginInfo[$plugin->getClassHandle()]))
		{
			return $this->_enabledPluginInfo[$plugin->getClassHandle()];
		}
	}

	/**
	 * Returns an array of class names found in a given plugin folder.
	 *
	 * @param BasePlugin $plugin         The plugin.
	 * @param string     $classSubfolder The subfolder to search.
	 * @param string     $classSuffix    The class suffix we’re looking for.
	 * @param bool       $autoload       Whether the found classes should be imported for the autoloader.
	 *
	 * @return array The class names.
	 */
	public function getPluginClasses(BasePlugin $plugin, $classSubfolder, $classSuffix, $autoload = true)
	{
		$classes = array();

		$pluginHandle = $plugin->getClassHandle();
		$pluginFolder = mb_strtolower($plugin->getClassHandle());
		$pluginFolderPath = craft()->path->getPluginsPath().$pluginFolder.'/';
		$classSubfolderPath = $pluginFolderPath.$classSubfolder.'/';

		if (IOHelper::folderExists($classSubfolderPath))
		{
			// Enums don't have an "Enum" suffix.
			if ($classSubfolder === 'enums')
			{
				$files = IOHelper::getFolderContents($classSubfolderPath, false);
			}
			else
			{
				// See if it has any files in ClassName*Suffix.php format.
				$filter = $pluginHandle.'(_.+)?'.$classSuffix.'\.php$';
				$files = IOHelper::getFolderContents($classSubfolderPath, false, $filter);
			}

			if ($files)
			{
				foreach ($files as $file)
				{
					$class = IOHelper::getFileName($file, false);
					$classes[] = $class;

					if ($autoload)
					{
						Craft::import("plugins.{$pluginFolder}.{$classSubfolder}.{$class}");
					}
				}
			}
		}

		return $classes;
	}

	/**
	 * Returns whether a plugin class exists.
	 *
	 * @param BasePlugin $plugin         The plugin.
	 * @param string     $classSubfolder The subfolder to search.
	 * @param string     $class          The class suffix we’re looking for.
	 * @param bool       $autoload       Whether the found class should be imported for the autoloader.
	 *
	 * @return bool Whether the class exists.
	 */
	public function doesPluginClassExist(BasePlugin $plugin, $classSubfolder, $class, $autoload = true)
	{
		$pluginFolder = mb_strtolower($plugin->getClassHandle());
		$classPath = craft()->path->getPluginsPath().$pluginFolder.'/'.$classSubfolder.'/'.$class.'.php';

		if (IOHelper::fileExists($classPath))
		{
			if ($autoload)
			{
				Craft::import("plugins.{$pluginFolder}.{$classSubfolder}.{$class}");
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	// Events
	// =========================================================================

	/**
	 * Fires an 'onLoadPlugins' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onLoadPlugins(Event $event)
	{
		$this->raiseEvent('onLoadPlugins', $event);
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
		throw new Exception(Craft::t('No plugin exists with the class “{class}”', array('class' => $handle)));
	}

	/**
	 * Finds and imports all of the auto-loadable classes for a given plugin.
	 *
	 * @param BasePlugin $plugin
	 *
	 * @return null
	 */
	private function _autoloadPluginClasses(BasePlugin $plugin)
	{
		foreach ($this->autoloadClasses as $classSuffix)
		{
			// *Controller's live in controllers/, etc.
			$classSubfolder = mb_strtolower($classSuffix).'s';
			$classes = $this->getPluginClasses($plugin, $classSubfolder, $classSuffix, true);

			if ($classSuffix == 'Service')
			{
				$this->_registerPluginServices($classes);
			}
		}
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
		$migrationsFolder = craft()->path->getPluginsPath().mb_strtolower($pluginHandle).'/migrations/';

		if (IOHelper::folderExists($migrationsFolder))
		{
			$migrations = array();
			$migrationFiles = IOHelper::getFolderContents($migrationsFolder, false, "(m(\d{6}_\d{6})_.*?)\.php$");

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
						throw new Exception(Craft::t('There was a problem saving to the migrations table: ').$this->_getFlattenedErrors($migration->getErrors()));
					}
				}
			}
		}
	}

	/**
	 * Registers any services provided by a plugin.
	 *
	 * @param array $classes
	 *
	 * @throws Exception
	 * @return null
	 */
	private function _registerPluginServices($classes)
	{
		$services = array();

		foreach ($classes as $class)
		{
			$parts = explode('_', $class);

			foreach ($parts as $index => $part)
			{
				$parts[$index] = lcfirst($part);
			}

			$serviceName = implode('_', $parts);
			$serviceName = mb_substr($serviceName, 0, - mb_strlen('Service'));

			if (!craft()->getComponent($serviceName, false))
			{
				// Register the component with the handle as (className or className_*) minus the "Service" suffix
				$nsClass = __NAMESPACE__.'\\'.$class;
				$services[$serviceName] = array('class' => $nsClass);
			}
			else
			{
				throw new Exception(Craft::t('The plugin “{handle}” tried to register a service “{service}” that conflicts with a core service name.', array('handle' => $class, 'service' => $serviceName)));
			}
		}

		craft()->setComponents($services, false);
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
		$class = $handle.'Plugin';
		$nsClass = __NAMESPACE__.'\\'.$class;

		// Skip the autoloader
		if (!class_exists($nsClass, false))
		{
			$path = craft()->path->getPluginsPath().mb_strtolower($handle).'/'.$class.'.php';

			if (($path = IOHelper::fileExists($path, false)) !== false)
			{
				require_once $path;
			}
			else
			{
				return null;
			}
		}

		if (!class_exists($nsClass, false))
		{
			return null;
		}

		$plugin = new $nsClass;

		// Make sure the plugin implements the IPlugin interface
		if (!$plugin instanceof IPlugin)
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
		$pluginsPath = craft()->path->getPluginsPath();
		$fullPath = $pluginsPath.mb_strtolower($iHandle).'/'.$iHandle.'Plugin.php';

		if (($file = IOHelper::fileExists($fullPath, true)) !== false)
		{
			$file = IOHelper::getFileName($file, false);
			return mb_substr($file, 0, mb_strlen($file) - mb_strlen('Plugin'));
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
		// TODO: Remove this check for Craft 3.
		if (PHP_VERSION_ID < 50400)
		{
			// Sort plugins by name
			array_multisort($names, $secondaryArray);
		}
		else
		{
			// Sort plugins by name
			array_multisort($names, SORT_NATURAL | SORT_FLAG_CASE, $secondaryArray);
		}
	}
}
