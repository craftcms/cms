<?php
namespace Blocks;

/**
 *
 */
class PluginsService extends Component
{
	private $_fileSystemPlugins = array();

	/**
	 * Returns all plugins, including uninstalled and disabled plugins
	 * @return array
	 */
	public function getAll()
	{
		$plugins = array();

		// Get the installed plugins indexed by class name
		$installedPlugins = Plugin::model()->findAll();
		$installedPluginsByClass = array();

		foreach ($installedPlugins as $plugin)
		{
			$installedPluginsByClass[$plugin->class] = $plugin;
		}

		foreach ($this->_getFileSystemPlugins() as $plugin)
		{
			if (isset($installedPluginsByClass[$plugin->classHandle]))
			{
				$plugin->installed = true;
				$plugin->enabled = $installedPluginsByClass[$plugin->classHandle]->enabled;
			}
			else
			{
				$plugin->installed = false;
				$plugin->enabled = false;
			}

			$plugins[] = $plugin;
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

				// Import the plugin class file if it exists
				if (!class_exists($fullClass))
				{
					$path = $pluginsPath.$folder.'/'.$shortClass.'Plugin.php';
					if (!file_exists($path))
						continue;

					require_once $path;
				}

				// Ignore if we couldn't find the plugin class
				if (!class_exists($fullClass))
					continue;

				$plugin = new $fullClass;
				$this->_fileSystemPlugins[] = $plugin;
			}
		}

		return $this->_fileSystemPlugins;
	}
}
