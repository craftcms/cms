<?php
namespace Craft;

/**
 *
 */
class PluginsService extends BaseApplicationComponent
{
	/**
	 * @var array The type of components plugins can have. Defined in app/etc/config/common.php.
	 */
	public $componentTypes;

	/**
	 * Stores whether plugins have been loaded yet for this request.
	 *
	 * @access private
	 * @var bool
	 */
	private $_pluginsLoaded = false;

	/**
	 * Stores all plugins, whether installed or not.
	 *
	 * @access private
	 * @var array
	 */
	private $_plugins = array();

	/**
	 * Stores all enabled plugins.
	 *
	 * @access private
	 * @var array
	 */
	private $_enabledPlugins = array();

	/**
	 * Stores all plugins in the system, regardless of whether they're installed/enabled or not.
	 *
	 * @access private
	 * @var array
	 */
	private $_allPlugins;

	/**
	 * List of the known component classes for each plugin,
	 * indexed by the component type, then the plugin handle.
	 *
	 * @access private
	 * @var array
	 */
	private $_pluginComponentClasses = array();

	/**
	 * Holds a list of all of the enabled plugin info indexed by the plugin class name.
	 *
	 * @access private
	 * @var array
	 */
	private $_enabledPluginInfo = array();

	/**
	 * Returns whether plugins have been loaded yet for this request.
	 *
	 * @return bool
	 */
	public function arePluginsLoaded()
	{
		return $this->_pluginsLoaded;
	}

	/**
	 * Loads the enabled plugins.
	 */
	public function loadPlugins()
	{
		if (!$this->_pluginsLoaded)
		{
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
					// Clean it up a bit
					$row['settings'] = JsonHelper::decode($row['settings']);
					$row['installDate'] = DateTime::createFromString($row['installDate']);

					$this->_enabledPluginInfo[$row['class']] = $row;

					$lcPluginHandle = strtolower($plugin->getClassHandle());
					$this->_plugins[$lcPluginHandle] = $plugin;
					$this->_enabledPlugins[$lcPluginHandle] = $plugin;
					$names[] = $plugin->getName();

					$plugin->setSettings($row['settings']);

					$plugin->isInstalled = true;
					$plugin->isEnabled = true;

					$this->_importPluginComponents($plugin);
					$this->_registerPluginServices($plugin->getClassHandle());
				}
			}

			// Sort plugins by name
			array_multisort($names, $this->_enabledPlugins);

			// Now that all of the components have been imported,
			// initialize all the plugins
			foreach ($this->_enabledPlugins as $plugin)
			{
				$plugin->init();
			}

			$this->_pluginsLoaded = true;

			// Fire an 'onLoadPlugins' event
			$this->onLoadPlugins(new Event($this));
		}
	}

	/**
	 * Returns a plugin.
	 *
	 * @param string $handle
	 * @param bool   $enabledOnly
	 * @return BasePlugin|null
	 */
	public function getPlugin($handle, $enabledOnly = true)
	{
		$lcPluginHandle = strtolower($handle);

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
	 * Returns all plugins, whether they're installed or not.
	 *
	 * @param bool $enabledOnly
	 * @return array
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
				$paths = array();

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
							$paths = array_merge($paths, IOHelper::getFolderContents($pluginFolderContent, false, ".*Plugin\.php"));
						}
					}

					if (is_array($paths) && count($paths) > 0)
					{
						foreach ($paths as $path)
						{
							$path = IOHelper::normalizePathSeparators($path);
							$fileName = IOHelper::getFileName($path, false);

							// Chop off the "Plugin" suffix
							$handle = substr($fileName, 0, strlen($fileName) - 6);

							$plugin = $this->getPlugin($handle, false);

							if ($plugin)
							{
								$this->_allPlugins[] = $plugin;
								$names[] = $plugin->getName();
							}
						}
					}

					if (!empty($names))
					{
						// Sort plugins by name
						array_multisort($names, $this->_allPlugins);
					}
				}
			}

			return $this->_allPlugins;
		}
	}

	/**
	 * Enables a plugin.
	 *
	 * @param $handle
	 * @throws Exception
	 * @return bool
	 */
	public function enablePlugin($handle)
	{
		$plugin = $this->getPlugin($handle, false);
		$lcPluginHandle = strtolower($plugin->getClassHandle());

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (!$plugin->isInstalled)
		{
			throw new Exception(Craft::t('“{plugin}” can’t be enabled because it isn’t installed yet.', array('plugin' => $plugin->getName())));
		}

		craft()->db->createCommand()->update('plugins',
			array('enabled' => 1),
			array('class' => $plugin->getClassHandle())
		);

		$plugin->isEnabled = true;
		$this->_enabledPlugins[$lcPluginHandle] = $plugin;

		return true;
	}

	/**
	 * Disables a plugin.
	 *
	 * @param $handle
	 * @throws Exception
	 * @return bool
	 */
	public function disablePlugin($handle)
	{
		$plugin = $this->getPlugin($handle);
		$lcPluginHandle = strtolower($plugin->getClassHandle());

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (!$plugin->isInstalled)
		{
			throw new Exception(Craft::t('“{plugin}” can’t be disabled because it isn’t installed yet.', array('plugin' => $plugin->getName())));
		}

		craft()->db->createCommand()->update('plugins',
			array('enabled' => 0),
			array('class' => $plugin->getClassHandle())
		);

		$plugin->isEnabled = false;
		unset($this->_enabledPlugins[$lcPluginHandle]);

		return true;
	}

	/**
	 * Installs a plugin.
	 *
	 * @param $handle
	 * @throws Exception
	 * @throws \Exception
	 * @return bool
	 */
	public function installPlugin($handle)
	{
		$plugin = $this->getPlugin($handle, false);
		$lcPluginHandle = strtolower($plugin->getClassHandle());

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if ($plugin->isInstalled)
		{
			throw new Exception(Craft::t('“{plugin}” is already installed.', array('plugin' => $plugin->getName())));
		}

		$transaction = craft()->db->beginTransaction();
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

			$this->_importPluginComponents($plugin);
			$plugin->createTables();

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		$plugin->onAfterInstall();

		return true;
	}

	/**
	 * Uninstalls a plugin by removing it's record from the database, deleting it's tables and foreign keys and running the plugin's uninstall method if it exists.
	 *
	 * @param $handle
	 * @throws Exception
	 * @throws \Exception
	 * @return bool
	 */
	public function uninstallPlugin($handle)
	{
		$plugin = $this->getPlugin($handle, false);
		$lcPluginHandle = strtolower($plugin->getClassHandle());

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (!$plugin->isInstalled)
		{
			throw new Exception(Craft::t('“{plugin}” is already uninstalled.', array('plugin' => $plugin->getName())));
		}

		if (!$plugin->isEnabled)
		{
			// Pretend that the plugin is enabled just for this request
			$plugin->isEnabled = true;
			$this->_enabledPlugins[$lcPluginHandle] = $plugin;
			$this->_importPluginComponents($plugin);
		}

		$plugin->onBeforeUninstall();

		$transaction = craft()->db->beginTransaction();
		try
		{
			$plugin->dropTables();

			// Remove the row from the database.
			craft()->db->createCommand()->delete('plugins', array('class' => $plugin->getClassHandle()));

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		$plugin->isEnabled = false;
		$plugin->isInstalled = false;
		unset($this->_enabledPlugins[$lcPluginHandle]);

		return true;
	}

	/**
	 * Saves a plugin's settings.
	 *
	 * @param BasePlugin $plugin
	 * @param mixed $settings
	 * @return bool
	 */
	public function savePluginSettings(BasePlugin $plugin, $settings)
	{
		// Give the plugin a chance to modify the settings
		$settings = $plugin->prepSettings($settings);
		$settings = JsonHelper::encode($settings);

		$affectedRows = craft()->db->createCommand()->update('plugins', array(
			'settings' => $settings
		), array(
			'class' => $plugin->getClassHandle()
		));

		return (bool) $affectedRows;
	}

	/**
	 * Calls a method on all plugins that have the method.
	 *
	 * @param string $method
	 * @param array $args
	 * @return array
	 */
	public function call($method, $args = array())
	{
		$result = array();
		$altMethod = 'hook'.ucfirst($method);

		foreach ($this->getPlugins() as $plugin)
		{
			if (method_exists($plugin, $method))
			{
				$result[$plugin->getClassHandle()] = call_user_func_array(array($plugin, $method), $args);
			}

			// TODO: Remove for 2.0
			else if (method_exists($plugin, $altMethod))
			{
				Craft::log('The “hook” prefix on the '.get_class($plugin).'::'.$altMethod.'() method name has been deprecated. It should be renamed to '.$method.'().', LogLevel::Warning);
				$result[$plugin->getClassHandle()] = call_user_func_array(array($plugin, $altMethod), $args);
			}
		}

		return $result;
	}

	/**
	 * Provides legacy support for craft()->plugins->callHook().
	 *
	 * @param string $method
	 * @param array $args
	 * @return array
	 * @deprecated Deprecated since 1.0
	 */
	public function callHook($method, $args = array())
	{
		Craft::log('The craft()->plugins->callHook() method has been deprecated. Use craft()->plugins->call() instead.', LogLevel::Warning);
		return $this->call($method, $args);
	}

	/**
	 * Returns all components of a certain type, across all plugins.
	 *
	 * @param $type
	 * @return array
	 */
	public function getAllComponentsByType($type)
	{
		$components = array();

		if (isset($this->componentTypes[$type]['instanceof']))
		{
			$instanceOf = $this->componentTypes[$type]['instanceof'];
		}
		else
		{
			$instanceOf = null;
		}

		foreach ($this->getPlugins() as $plugin)
		{
			$pluginHandle = $plugin->getClassHandle();
			$classes = $this->getPluginComponentClassesByType($pluginHandle, $type);

			foreach ($classes as $class)
			{
				$component = craft()->components->initializeComponent($class, $instanceOf);

				if ($component)
				{
					$components[] = $component;
				}
			}
		}

		return $components;
	}

	/**
	 * Returns all of a plugin's component class names of a certain type.
	 *
	 * @param string $pluginHandle
	 * @param string $type
	 * @return array
	 */
	public function getPluginComponentClassesByType($pluginHandle, $type)
	{
		// Make sure plugins can actually have this type of component
		if (!isset($this->componentTypes[$type]))
		{
			return array();
		}

		if (isset($this->_pluginComponentClasses[$type][$pluginHandle]))
		{
			return $this->_pluginComponentClasses[$type][$pluginHandle];
		}
		else
		{
			return array();
		}
	}

	/**
	 * Returns whether the given plugin's local version number is greater than the record we have in the database.
	 *
	 * @param BasePlugin $plugin
	 * @return bool
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
	 * Returns an array of the stored info for a given plugin.
	 *
	 * @param BasePlugin $plugin
	 * @return array|null
	 */
	public function getPluginInfo(BasePlugin $plugin)
	{
		if (isset($this->_enabledPluginInfo[$plugin->getClassHandle()]))
		{
			return $this->_enabledPluginInfo[$plugin->getClassHandle()];
		}
	}

	// Events

	/**
	 * Fires an 'onLoadPlugins' event.
	 *
	 * @param Event $event
	 */
	public function onLoadPlugins(Event $event)
	{
		$this->raiseEvent('onLoadPlugins', $event);
	}

	// Private Methods

	/**
	 * Throws a "no plugin exists" exception.
	 *
	 * @access private
	 * @param string $handle
	 * @throws Exception
	 */
	private function _noPluginExists($handle)
	{
		throw new Exception(Craft::t('No plugin exists with the class “{class}”', array('class' => $handle)));
	}

	/**
	 * Finds and imports all of the supported component classes for a given plugin.
	 *
	 * @access private
	 * @param BasePlugin $plugin
	 */
	private function _importPluginComponents(BasePlugin $plugin)
	{
		$pluginHandle = $plugin->getClassHandle();
		$lcPluginHandle = strtolower($plugin->getClassHandle());
		$pluginFolder = craft()->path->getPluginsPath().$lcPluginHandle.'/';

		foreach ($this->componentTypes as $type => $typeInfo)
		{
			$folder = $pluginFolder.$typeInfo['subfolder'];

			if (IOHelper::folderExists($folder))
			{
				// See if it has any files in ClassName*Suffix.php format.
				$filter = $pluginHandle.'(_.+)?'.$typeInfo['suffix'].'\.php$';
				$files = IOHelper::getFolderContents($folder, false, $filter);

				if ($files)
				{
					foreach ($files as $file)
					{
						// Get the class name
						$class = IOHelper::getFileName($file, false);

						// Import the class.
						Craft::import('plugins.'.$lcPluginHandle.'.'.$typeInfo['subfolder'].'.'.$class);

						// Remember it
						$this->_pluginComponentClasses[$type][$pluginHandle][] = $class;
					}
				}
			}
		}
	}

	/**
	 * Registers any services provided by a plugin.
	 *
	 * @access private
	 * @param string $handle
	 * @throws Exception
	 * @return void
	 */
	private function _registerPluginServices($handle)
	{
		$classes = $this->getPluginComponentClassesByType($handle, 'service');

		$services = array();

		foreach ($classes as $class)
		{
			$parts = explode('_', $class);

			foreach ($parts as $index => $part)
			{
				$parts[$index] = lcfirst($part);
			}

			$serviceName = implode('_', $parts);
			$serviceName = substr($serviceName, 0, -strlen('Service'));

			if (!craft()->getComponent($serviceName, false))
			{
				// Register the component with the handle as (className or className_*) minus the "Service" suffix
				$nsClass = __NAMESPACE__.'\\'.$class;
				$services[$serviceName] = array('class' => $nsClass);
			}
			else
			{
				throw new Exception(Craft::t('The plugin “{handle}” tried to register a service “{service}” that conflicts with a core service name.', array('handle' => $handle, 'service' => $serviceName)));
			}
		}

		craft()->setComponents($services, false);
	}

	/**
	 * Returns a new plugin instance based on its class handle.
	 *
	 * @param $handle
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
			$path = craft()->path->getPluginsPath().strtolower($handle).'/'.$class.'.php';

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

		// Make sure the plugin implements the BasePlugin abstract class
		if (!$plugin instanceof BasePlugin)
		{
			return null;
		}

		return $plugin;
	}

	/**
	 * Returns the actual plugin class handle based on a case-insensitive handle.
	 *
	 * @param $iHandle
	 * @return bool|string
	 */
	private function _getPluginHandleFromFileSystem($iHandle)
	{
		$pluginsPath = craft()->path->getPluginsPath();
		$fullPath = $pluginsPath.strtolower($iHandle).'/'.$iHandle.'Plugin.php';

		if (($file = IOHelper::fileExists($fullPath, true)) !== false)
		{
			$file = IOHelper::getFileName($file, false);
			return substr($file, 0, strlen($file) - strlen('Plugin'));
		}

		return false;
	}
}
