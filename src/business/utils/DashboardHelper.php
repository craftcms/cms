<?php

class DashboardHelper
{
	// TODO: Eventually stuff like this should go into a global messaging service.
	public static function getAlerts()
	{
		$blocksUpdateData = Blocks::app()->update->getUpdateInfo();

		$alerts = array();

		if ($blocksUpdateData->licenseStatus == LicenseKeyStatus::InvalidKey)
			$alerts[] = 'The license key you’re using isn’t authorized to run Blocks '.Blocks::getEdition().' on '.Blocks::app()->request->getServerName().'. <a href="">Manage my licenses</a>';

		if ($blocksUpdateData->newerReleases !== null && count($blocksUpdateData->newerReleases) > 0)
			if (Blocks::app()->update->criticalBlocksUpdateAvailable($blocksUpdateData->newerReleases))
				$alerts[] = 'There is a critical update for Blocks available. '.BlocksHtml::link('Update now', 'settings/updates');

		return $alerts;
	}
}
