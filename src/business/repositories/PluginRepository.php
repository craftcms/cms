<?php

class PluginRepository extends CApplicationComponent implements IPluginRepository
{
	public function getAllInstalledPlugins()
	{
		return Plugins::model()->findAll();
	}

	public function getAllInstalledPluginHandlesAndVersions()
	{
		$installedPlugins = $this->getAllInstalledPlugins();

		if(!ArrayHelper::IsValidArray($installedPlugins))
			return null;

		$pluginNamesAndVersions = array();

		foreach($installedPlugins as $plugin)
		{
			$pluginVersionInfo['handle'] = $plugin->name;
			$pluginVersionInfo['installedVersion'] = $plugin->version;

			$pluginNamesAndVersions[$plugin->name] = $pluginVersionInfo;
		}

		return $pluginNamesAndVersions;
	}
}
