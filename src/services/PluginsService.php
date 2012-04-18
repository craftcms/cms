<?php
namespace Blocks;

/**
 *
 */
class PluginsService extends Component
{
	private $_fileSystemPlugins = array();

	/**
	 * Returns all plugins with flags to include plugins on the filesystem, but not yet installed and including plugins that
	 * are installed, but disabled.
	 * @param bool $includeNotInstalled
	 * @param bool $includeDisabled
	 * @return array
	 */
	public function getAll($includeNotInstalled = false, $includeDisabled = false)
	{
		$plugins = array();

		// Get all of the plugins from the database.
		$installedPlugins = Plugin::model()->findAll();

		// Match all of the plugins registered in the database against the file system to make sure they still exist.
		foreach ($this->_getFileSystemPlugins() as $fileSystemPluginClass => $fileSystemPluginInfo)
		{
			foreach ($installedPlugins as $installedPlugin)
			{
				if ($installedPlugin->class === $fileSystemPluginInfo[0])
				{
					// Instantiate the plugin instance from the active record.
					$installedPluginInstance = new $fileSystemPluginClass;
					$installedPluginInstance->name = $installedPlugin->name;
					$installedPluginInstance->class = $installedPlugin->classHandle;
					$installedPluginInstance->enabled = $installedPlugin->enabled;
					$installedPluginInstance->installed = true;

					$plugins[$installedPlugin->class] = $installedPluginInstance;
				}
			}
		}

		// Include plugins not installed yet?
		if ($includeNotInstalled)
		{
			// Loop through entries in the plugins folder
			foreach ($this->_getFileSystemPlugins() as $fileSystemPluginClass => $fileSystemPluginInfo)
			{
				// Make sure it's not already in our plugins list.
				if (!isset($plugins[$fileSystemPluginInfo[0]]))
				{
					// Instantiate it and set a few default values.
					$notInstalledPlugin = new $fileSystemPluginClass;
					$notInstalledPlugin->installed = false;
					$notInstalledPlugin->enabled = false;
					$notInstalledPlugin->class = $notInstalledPlugin->classHandle;

					// Add to our plugins list.
					$plugins[$notInstalledPlugin->classHandle] = $notInstalledPlugin;
				}
			}
		}

		// If they don't want disabled plugins included, we filter them out here.
		if (!$includeDisabled)
		{
			foreach ($plugins as $plugin)
			{
				if (!$plugin->enabled)
					unset($plugins[$plugin->class]);
			}
		}

		return $plugins;
	}

	/**
	 * @return Plugin
	 */
	public function getAllInstalledPlugins()
	{
		return Plugin::model()->findAll();
	}

	/**
	 * @return array|null
	 */
	public function getAllInstalledPluginHandlesAndVersions()
	{
		$installedPlugins = $this->allInstalledPlugins;

		if(!is_array($installedPlugins))
			return null;

		$pluginNamesAndVersions = array();

		foreach($installedPlugins as $plugin)
		{
			$pluginVersionInfo['handle'] = $plugin->class;
			$pluginVersionInfo['localVersion'] = $plugin->version;

			$pluginNamesAndVersions[$plugin->class] = $pluginVersionInfo;
		}

		return $pluginNamesAndVersions;
	}

	/**
	 * @return array
	 */
	private function _getFileSystemPlugins()
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

				$this->_fileSystemPlugins[$fullClass] = array($shortClass, $path);
			}
		}

		return $this->_fileSystemPlugins;
	}
}
