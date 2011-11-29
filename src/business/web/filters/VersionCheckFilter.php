<?php

class VersionCheckFilter extends CFilter
{
	protected function preFilter($filterChain)
	{
		// Only run on the CP side.
		if (Blocks::app()->request->getCMSRequestType() == RequestType::Site)
			return true;

		if (Blocks::app()->controller->id == 'update')
			return true;

		// Don't execute this if we're already in the install module on the default controller.
		if (Blocks::app()->controller->getModule() !== null)
			if (Blocks::app()->controller->getModule()->id == 'install' && Blocks::app()->controller->id == 'default')
				return true;

		if (Blocks::app()->controller->id == 'site' && Blocks::app()->controller->action->id == 'error')
			return true;

		if (($keys = Blocks::app()->site->getSiteLicenseKeys()) == null || empty($keys))
		{
			$blocksUpdateInfo['blocksLicenseStatus'] = LicenseKeyStatus::MissingKey;
			Blocks::app()->request->setBlocksUpdateInfo($blocksUpdateInfo);
			return true;
		}

		$blocksUpdateInfo = Blocks::app()->site->versionCheck();
		if ($blocksUpdateInfo !== null)
			Blocks::app()->request->setBlocksUpdateInfo($blocksUpdateInfo);

		return true;
	}

	private function buildPluginStatusMessages($pluginsInfo)
	{
		$pluginsToUpdate = 0;
		$deletedPlugins = 0;
		$pluginStatusMessages = array();

		foreach ($pluginsInfo as $pluginInfo)
		{
			if (isset($pluginInfo['status']))
			{
				if ($pluginInfo['status'] == PluginVersionUpdateStatus::UpdateAvailable)
					$pluginsToUpdate++;

				if ($pluginInfo['status'] == PluginVersionUpdateStatus::Deleted)
					$deletedPlugins++;
			}
		}

		if ($pluginsToUpdate > 0)
			$pluginStatusMessages[] = $pluginsToUpdate.' of your installed plugins have updates. '.BlocksHtml::link('Please update now.', array('index'));

		if ($deletedPlugins > 0)
			$pluginStatusMessages[] = $deletedPlugins.' of your installed plugins have been deleted. '.BlocksHtml::link('Find out why.', array('index'));

		return $pluginStatusMessages;
	}
}
