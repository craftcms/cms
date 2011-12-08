<?php

class DashboardHelper
{
	public static function getAlerts($fetch = false)
	{
		$blocksUpdateInfo = Blocks::app()->request->getBlocksUpdateInfo($fetch);

		$alerts = array();

		if ($blocksUpdateInfo['blocksLicenseStatus'] == LicenseKeyStatus::InvalidKey)
			$alerts[] = 'The license key you’re using isn’t authorized to run Blocks '.Blocks::getEdition().' on '.Blocks::app()->request->getServerName().'. <a href="">Manage my licenses</a>';

		if (self::criticalUpdateAvailable($blocksUpdateInfo))
			$alerts[] = 'There is a critical update for Blocks available. <a href="">Update now</a>';

		return $alerts;
	}

	private static function criticalUpdateAvailable($blocksUpdateInfo)
	{
		if (isset($blocksUpdateInfo['blocksLatestCoreReleases']))
		{
			foreach ($blocksUpdateInfo['blocksLatestCoreReleases'] as $release)
			{
				if ($release['critical'])
				{
					return true;
				}
			}
		}

		/*foreach ($blocksUpdateInfo['pluginNamesAndVersions'] as $plugin)
		{
			foreach ($plugin['newerReleases'] as $release)
			{
				if ($release['critical'])
				{
					return true;
				}
			}
		}*/
	}
}
