<?php

class PluginService extends CApplicationComponent implements IPluginService
{
	public function getAllInstalledPlugins()
	{
		return Plugins::model()->findAll();
	}

	public function getAllInstalledPluginHandlesAndVersions()
	{
		$installedPlugins = $this->getAllInstalledPlugins();

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
