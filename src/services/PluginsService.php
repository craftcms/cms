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
	private $_installedPlugins = array();

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

		return $pluginInstanceCopy;
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
	 * Accepts a plugin class short name (minus the namespace and 'Plugin' suffix) and returns an instantiated plugin either
	 * from a previously created saved instance or a new instance.
	 * @param $className
	 * @return mixed
	 */
	public function getPlugin($className)
	{
		if (!isset($this->_pluginInstances[$className]))
		{
			$this->_getPluginInternal($className);
		}

		return $this->_pluginInstances[$className];
	}

	/**
	 * Returns a list of enabled plugins indexed by their class name along with their version.
	 * @return array|null
	 */
	public function getEnabledPluginClassNamesAndVersions()
	{
		$plugins = $this->enabled;

		if (!$plugins)
			return null;

		$pluginClassNamesAndVersions = array();

		foreach($plugins as $plugin)
		{
			$pluginClassNamesAndVersions[$plugin->class] = $plugin->version;
		}

		return $pluginClassNamesAndVersions;
	}

	/**
	 * Attempts to find a plugin by classname by first looking in the db and validating against the file system (for installed plugins)
	 * and then looking on the file system and making sure it doesn't exist in the db (for non-installed plugins).
	 * @param $className
	 * @return mixed
	 */
	private function _getPluginInternal($className)
	{
		$plugin = Plugin::model()->findByAttributes(array(
			'class' => $className
		));

		if ($this->_validatePluginClassAgainstFileSystem($plugin))
		{
			$this->_instantiatePlugin($className, $plugin->name, $plugin->enabled, $plugin->installed);
			return;
		}

		if ($this->_validatePluginClassAgainstDatabases($className))
		{
			$this->_instantiatePlugin($className, $plugin->name, $plugin->enabled, $plugin->installed);
			return;
		}
	}

	/**
	 * Will instantiate the given class name, populate some default values and add it to our internal list $this->_pluginInstances.
	 * @param $className
	 * @param $pluginName
	 * @param $enabled
	 * @param $installed
	 */
	private function _instantiatePlugin($className, $pluginName, $enabled, $installed)
	{
		// Get plugins from the file system.
		$fileSystemPlugins = $this->_getFileSystemPluginsInternal();

		// Instantiate the plugin instance from the active record object.
		$pluginInstance = new $fileSystemPlugins[$className][0];

		if ($pluginName)
			$pluginInstance->name = $pluginName;

		$pluginInstance->class = $className;
		$pluginInstance->enabled = $enabled;
		$pluginInstance->installed = $installed;

		// Add to our list.
		$this->_pluginInstances[$className] = $pluginInstance;
	}

	/**
	 * Gets all plugins in db and filesystem regardless of their status.
	 * @return array
	 */
	private function _getAllPluginsInternal()
	{
		// Get all of the plugins from the database.
		$installedPlugins = $this->_getInstalledPluginsInternal();

		// Get all of the plugins on the file system
		$fileSystemPlugins = $this->_getFileSystemPluginsInternal();

		// Match all of the plugins registered in the database against the file system to make sure they still exist.
		foreach ($installedPlugins as $installedPlugin)
		{
			if ($this->_validatePluginClassAgainstFileSystem($installedPlugin->class))
				$this->_instantiatePlugin($installedPlugin->class, $installedPlugin->name, $installedPlugin->enabled, true);
		}

		// Let's find any plugins that are on the file system, but not installed yet.
		foreach ($fileSystemPlugins as $fileSystemPluginClass => $fileSystemPluginInfo)
		{
			// Found it, instantiate it and set a few default values.
			if ($this->_validatePluginClassAgainstDatabases($fileSystemPluginClass))
				$this->_instantiatePlugin($fileSystemPluginClass, null, false, false);
		}
	}

	/**
	 * Takes a given plugin class name and checks to see if it exists in the database or not.
	 * @param $className
	 * @return bool
	 */
	private function _validatePluginClassAgainstDatabases($className)
	{
		$installedPlugins = $this->_getInstalledPluginsInternal();
		$notInstalled = true;

		foreach ($installedPlugins as $installedPlugin)
		{
			if ($installedPlugin->class === $className)
			{
				$notInstalled = false;
				break;
			}
		}

		return $notInstalled;
	}

	/**
	 * Takes a given plugin class name and checks to see if exists on the file system in the plugins directory.
	 * @param $className
	 * @return bool
	 */
	private function _validatePluginClassAgainstFileSystem($className)
	{
		$fileSystemPlugins = $this->_getFileSystemPluginsInternal();

		if (array_key_exists($className, $fileSystemPlugins))
			return true;

		return false;
	}

	/**
	 * Gets all plugins recorded in the db.
	 * @return array
	 */
	private function _getInstalledPluginsInternal()
	{
		if (!$this->_installedPlugins)
		{
			$this->_installedPlugins = Plugin::model()->findAll();
		}

		return $this->_installedPlugins;
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

				// Import the plugin class file if it exists
				if (!class_exists($fullClass))
				{
					if (!file_exists($path))
						continue;

					require_once $path;
				}

				// Ignore if we couldn't find the plugin class
				if (!class_exists($fullClass))
					continue;

				$this->_fileSystemPlugins[$shortClass] = array($fullClass, $path);
			}
		}

		return $this->_fileSystemPlugins;
	}
}
