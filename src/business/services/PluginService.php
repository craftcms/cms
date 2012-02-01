<?php
namespace Blocks;

/**
 *
 */
class PluginService extends \CApplicationComponent
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
			$pluginVersionInfo['handle'] = $plugin->name;
			$pluginVersionInfo['localVersion'] = $plugin->version;

			$pluginNamesAndVersions[$plugin->name] = $pluginVersionInfo;
		}

		return $pluginNamesAndVersions;
	}
}
