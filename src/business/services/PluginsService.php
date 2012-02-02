<?php
namespace Blocks;

/**
 *
 */
class PluginsService extends \CApplicationComponent
{
	/**
	 * @return Plugins
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
}
