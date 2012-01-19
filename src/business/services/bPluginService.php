<?php

/**
 *
 */
class bPluginService extends CApplicationComponent
{
	/**
	 * @return Plugins
	 */
	public function getAllInstalledPlugins()
	{
		return bPlugin::model()->findAll();
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
