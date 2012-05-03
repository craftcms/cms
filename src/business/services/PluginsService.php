<?php
namespace Blocks;

/**
 *
 */
class PluginsService extends Component
{
	// A list of all all of the plugins in plugin directory.
	private $_fileSystemPlugins = array();

	// A list of all of the plugins in the database.
	private $_dbPlugins = array();

	// Holds the list of instantiated plugins for the current request.
	private $_pluginInstances = array();

	/**
	 * Returns all plugins with flags to include plugins on the filesystem, but not yet installed and including plugins that
	 * are installed, but disabled.
	 * @param bool $includeNotInstalled
	 * @param bool $includeDisabled
	 * @return array
	 */
	public function getAll($includeNotInstalled = false, $includeDisabled = false)
	{
		// Refresh the list of $this->_pluginInstances to populate all possible plugins.
		$this->_getAllPluginsInternal();

		// Don't want to alter the original.
		$pluginInstanceCopy = $this->_pluginInstances;

		// Should we move plugins that are not installed?
		if (!$includeNotInstalled)
		{
			foreach ($pluginInstanceCopy as $plugin)
			{
				if (!$plugin->installed)
					unset($pluginInstanceCopy[$plugin->class]);
			}
		}

		// If they don't want disabled plugins included, we filter them out here.
		if (!$includeDisabled)
		{
			foreach ($pluginInstanceCopy as $plugin)
			{
				if (!$plugin->enabled)
					unset($pluginInstanceCopy[$plugin->class]);
			}
		}

		// Sort by plugin name.
		usort($pluginInstanceCopy, array($this, '_pluginNameSort'));

		return $pluginInstanceCopy;
	}

	/**
	 * Used for sorting the pluginInstances by plugin name.
	 * @param $a
	 * @param $b
	 * @return bool
	 */
	private function _pluginNameSort($a, $b)
	{
		return $a['name'] > $b['name'];
	}

	/**
	 * Returns a list of plugins that are installed and may or may not be enabled.
	 * @return array
	 */
	public function getInstalled()
	{
		return $this->getAll(false, true);
	}

	/**
	 * Returns a list of plugins that are installed and enabled.
	 * @return array
	 */
	public function getEnabled()
	{
		return $this->getAll(false, false);
	}

	/**
	 * Accepts a plugin case insensitive class short name (minus the namespace and 'Plugin' suffix) and returns an
	 * instantiated plugin either from a previously created saved instance or a new instance.
	 * @param $className
	 * @return mixed
	 */
	public function getPlugin($className)
	{
		$normalizedClassName = $this->_normalizeClassName($className);

		if (!isset($this->_pluginInstances[$normalizedClassName]))
		{
			$this->_getPluginInternal($normalizedClassName);
		}

		return $this->_pluginInstances[$normalizedClassName];
	}

	/**
	 * Returns a list of enabled plugins indexed by their class name along with their version.
	 * @return array
	 */
	public function getEnabledPluginClassNamesAndVersions()
	{
		$plugins = $this->enabled;

		$pluginClassNamesAndVersions = array();

		foreach($plugins as $plugin)
		{
			$pluginClassNamesAndVersions[$plugin->class] = $plugin->version;
		}

		return $pluginClassNamesAndVersions;
	}

	/**
	 * Enables a plugin.
	 * @param $className
	 * @return bool
	 */
	public function enable($className)
	{
		$plugin = $this->getPlugin($className);

		$plugin->enabled = true;
		if ($plugin->save())
			return true;
		else
			return false;
	}

	/**
	 * Disables a plugin.
	 * @param $className
	 * @return bool
	 */
	public function disable($className)
	{
		$plugin = $this->getPlugin($className);

		$plugin->enabled = false;
		if ($plugin->save())
			return true;
		else
			return false;
	}

	/**
	 * Installs a plugin.
	 * @param $className
	 * @return bool
	 */
	public function install($className)
	{
		$plugin = $this->getPlugin($className);
		$plugin->enabled = true;

		if ($plugin->save())
		{
			$plugin->installed = true;
			return true;
		}
		else
			return false;
	}

	/**
	 * Uninstalls a plugin by removing it's record from the database.
	 * @param $className
	 * @return bool
	 */
	public function uninstall($className)
	{
		$plugin = $this->getPlugin($className);

		if ($plugin->delete())
		{
			unset($this->_pluginInstances[$className]);
			return true;
		}
		else
			return false;
	}

	/**
	 * Attempts to find a plugin by classname by first looking in the db and validating against the file system
	 * (for installed plugins) and then looking on the file system and making sure it doesn't exist in the db (for non-installed plugins).
	 * @param $className
	 * @return mixed
	 */
	private function _getPluginInternal($className)
	{
		// See if we can find the plugin class name in the database.
		$dbPlugin = Plugin::model()->findByAttributes(array(
			'class' => $className
		));

		// Plugin was found in the database so we at the very least know it has been installed before.
		if ($dbPlugin)
		{
			// Check and see if it still exists on the file system.
			if ($this->_validatePluginClassAgainstFileSystem($dbPlugin->class))
			{
				// Instantiate it and add it to the plugin instance list.
				$this->_instantiatePlugin($dbPlugin);
				return;
			}
		}
		else
		{
			// Plugin was not found in the database, see if it exists on the file system.
			if ($this->_validatePluginClassAgainstFileSystem($className))
			{
				// On the file system, instantiate and add to the plugin instance list.
				$this->_instantiatePlugin(null, $className);
				return;
			}
		}
	}

	/**
	 * Gets all plugins in db and filesystem regardless of their status.
	 * @return array
	 */
	private function _getAllPluginsInternal()
	{
		// Get all of the plugins from the database.
		$dbPlugins = $this->_getDbPluginsInternal();

		// Get all of the plugins on the file system
		$fileSystemPlugins = $this->_getFileSystemPluginsInternal();

		// Match all of the plugins registered in the database against the file system to make sure they still exist.
		foreach ($dbPlugins as $dbPlugin)
		{
			if ($this->_validatePluginClassAgainstFileSystem($dbPlugin->class))
			{
				$this->_instantiatePlugin($dbPlugin);
			}
		}

		// Let's find any plugins that are on the file system, but not installed yet.
		foreach ($fileSystemPlugins as $fileSystemPluginClass => $fileSystemPluginInfo)
		{
			// Found it, instantiate it and set a few default values.
			if (!$this->_validatePluginClassAgainstDatabase($fileSystemPluginClass))
				$this->_instantiatePlugin(null, $fileSystemPluginClass);
		}
	}

	/**
	 * Will instantiate the given plugin. If the plugin already exists in the datbase, (installed) the instance will be of type
	 * $plugin->class.  Otherwise, (not installed) $plugin should be null and $className will be the type of the plugin instance created.
	 * The created plugin is added to the internal plugin instance list.
	 * @param $plugin
	 * @param $className
	 */
	private function _instantiatePlugin($plugin, $className = null)
	{
		$existing = $plugin && !$className;

		// Get plugins from the file system.
		$fileSystemPlugins = $this->_getFileSystemPluginsInternal();

		if ($existing)
		{
			$pluginInstance = Plugin::model()->populateSubclassRecord($plugin);
			$pluginInstance->installed = true;
			$className = $pluginInstance->class;
		}
		else
		{
			// Instantiate the plugin instance from the active record object.
			$pluginInstance = new $fileSystemPlugins[$className][0];
			$pluginInstance->installed = false;
			$pluginInstance->enabled = false;
			$pluginInstance->class = $className;
		}

		// Add to our list.
		$this->_pluginInstances[$className] = $pluginInstance;
	}

	/**
	 * Takes a given plugin class name and checks to see if it exists in the database or not.
	 * @param $className
	 * @return bool
	 */
	private function _validatePluginClassAgainstDatabase($className)
	{
		$dbPlugins = $this->_getDbPluginsInternal();
		$installed = false;

		foreach ($dbPlugins as $dbPlugin)
		{
			if ($dbPlugin->class === $className)
			{
				$installed = true;
				break;
			}
		}

		return $installed;
	}

	/**
	 * Takes a given plugin class name and checks to see if exists on the file system in the plugins directory.
	 * @param $className
	 * @return bool
	 */
	private function _validatePluginClassAgainstFileSystem($className)
	{
		$fileSystemPlugins = $this->_getFileSystemPluginsInternal();

		if (array_key_exists(strtolower($className), array_change_key_case($fileSystemPlugins, CASE_LOWER)))
			return true;

		return false;
	}

	/**
	 * Will take a plugin class name (presumably lower cased), match it to the plugins on the file system and return
	 * the correct casing for the class name.
	 * @param $className
	 * @return null
	 */
	private function _normalizeClassName($className)
	{
		$fileSystemPlugins = $this->_getFileSystemPluginsInternal();

		$index = array_search(strtolower($className), array_keys(array_change_key_case($fileSystemPlugins, CASE_LOWER)));

		if ($index === false)
			return null;

		$keys = array_keys($fileSystemPlugins);
		return $keys[$index];
	}

	/**
	 * Gets all plugins recorded in the db.
	 * @return array
	 */
	private function _getDbPluginsInternal()
	{
		if (!$this->_dbPlugins)
		{
			$this->_dbPlugins = Plugin::model()->findAll();
		}

		return $this->_dbPlugins;
	}

	/**
	 * Gets all of the plugins currently on the file system.
	 * @return array
	 */
	private function _getFileSystemPluginsInternal()
	{
		if (!$this->_fileSystemPlugins)
		{
			$pluginsPath = b()->path->pluginsPath;
			$folders = scandir($pluginsPath);

			foreach ($folders as $folder)
			{
				// Ignore files and relative directories
				if (strncmp($folder, '.', 1) === 0 || !is_dir($pluginsPath.$folder))
					continue;

				$shortClass = $folder;
				$fullClass = __NAMESPACE__.'\\'.$shortClass.'Plugin';
				$path = $pluginsPath.$folder.'/'.$shortClass.'Plugin.php';

				// Skip the autoloader
				if (!class_exists($fullClass, false))
					require_once $path;

				// Ignore if we couldn't find the plugin class
				if (!class_exists($fullClass, false))
					continue;

				$this->_fileSystemPlugins[$shortClass] = array($fullClass, $path);
			}
		}

		return $this->_fileSystemPlugins;
	}
}
