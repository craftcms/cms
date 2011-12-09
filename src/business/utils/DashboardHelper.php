<?php

class DashboardHelper
{
	// TODO: Eventually stuff like this should go into a global messaging service.
	public static function getAlerts($fetch = false)
	{
		$blocksUpdateInfo = Blocks::app()->update->blocksUpdateInfo($fetch);

		$alerts = array();

		if ($blocksUpdateInfo['blocksLicenseStatus'] == LicenseKeyStatus::InvalidKey)
			$alerts[] = 'The license key you’re using isn’t authorized to run Blocks '.Blocks::getEdition().' on '.Blocks::app()->request->getServerName().'. <a href="">Manage my licenses</a>';

		if (isset($blocksUpdateInfo['blocksLatestCoreReleases']))
			if (Blocks::app()->update->criticalBlocksUpdateAvailable($blocksUpdateInfo['blocksLatestCoreReleases']))
				$alerts[] = 'There is a critical update for Blocks available. '.BlocksHtml::link('Update now', 'settings/updates');

		return $alerts;
	}
}
