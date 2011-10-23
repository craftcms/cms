<?php

class VersionCheckFilter extends CFilter
{
	protected function preFilter($filterChain)
	{
		// Don't execute this if we're already in the update module on the default controller.
		if(Blocks::app()->controller->getModule() !== null)
			if((Blocks::app()->controller->getModule()->id == 'update' || Blocks::app()->controller->getModule()->id == 'install') && Blocks::app()->controller->id == 'default')
				return true;

		if(Blocks::app()->controller->id == 'site' && Blocks::app()->controller->action->id == 'error')
			return true;

		$responseVersionInfo = Blocks::app()->coreRepo->versionCheck();

		if($responseVersionInfo != null)
		{
			$blocksStatusMessages = $this->buildBlocksStatusMessages($responseVersionInfo);
			$pluginStatusMessages = $this->buildPluginStatusMessages($responseVersionInfo['pluginNamesAndVersions']);
			$statusMessages = array_merge($blocksStatusMessages, $pluginStatusMessages);

			if (count($statusMessages) == 0)
			{
				$statusMessages[] = 'Blocks is up to date and everything is great!';
			}

			Blocks::app()->user->setFlash('notice', $statusMessages);
		}

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
				 {
					$pluginsToUpdate++;
				 }

				if ($pluginInfo['status'] == PluginVersionUpdateStatus::Deleted)
				{
					$deletedPlugins++;
				}
			}
		}

		if ($pluginsToUpdate > 0)
		{
			$pluginStatusMessages[] = $pluginsToUpdate.' of your installed plugins have updates. '.BlocksHtml::link('Please update now.', array('index'));
		}

		if ($deletedPlugins > 0)
		{
			$pluginStatusMessages[] = $deletedPlugins.' of your installed plugins have been deleted. '.BlocksHtml::link('Find out why.', array('index'));
		}

		return $pluginStatusMessages;
	}

	private function buildBlocksStatusMessages($blocksVersionInfo)
	{
		$blocksStatusMessages = array();

		if ($blocksVersionInfo['blocksVersionUpdateStatus'] == BlocksVersionUpdateStatus::UpdateAvailable)
		{
			$blocksStatusMessages[] = 'You are '.count($blocksVersionInfo['blocksLatestCoreReleases']).' Blocks releases behind. The latest is v'.$blocksVersionInfo['blocksLatestCoreReleases'][0]['Version'].'.'.$blocksVersionInfo['blocksLatestCoreReleases'][0]['BuildNumber'].'. '.BlocksHtml::link('Please update now.', array('update/index'));
		}

		foreach($blocksVersionInfo['blocksLicenseStatus'] as $licenseMessage)
		{
			switch ($licenseMessage)
			{
				case LicenseKeyStatus::InvalidDomain:
					$blocksStatusMessages[] = "It appears Blocks is running on a domain that it wasn't licensed for.";
					break;

				case LicenseKeyStatus::UnknownKey:
					$blocksStatusMessages[] = "Blocks doesn't recognize your license key.";
					break;

				case LicenseKeyStatus::WrongEdition:
					$blocksStatusMessages[] = "It appears the Blocks version you are running isn't the correct one for your license key.";
					break;
			}
		}

		return $blocksStatusMessages;
	}
}
