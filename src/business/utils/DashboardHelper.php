<?php

/**
 *
 */
class DashboardHelper
{
	/**
	 * @static
	 * @param bool $fetch
	 * @return array
	 * @todo Eventually stuff like this should go into a global messaging service.
	 */
	public static function getAlerts($fetch = false)
	{
		$alerts = array();

		if (Blocks::app()->update->isUpdateInfoCached() || $fetch)
		{
			$blocksUpdateInfo = Blocks::app()->update->updateInfo;

			if (Blocks::app()->site->licenseKeyStatus == bLicenseKeyStatus::InvalidKey)
				$alerts[] = 'The license key you’re using isn’t authorized to run Blocks '.Blocks::getEdition().' on '.Blocks::app()->request->serverName.'. <a href="">Manage my licenses</a>';

			if ($blocksUpdateInfo->newerReleases !== null && count($blocksUpdateInfo->newerReleases) > 0)
				if (Blocks::app()->update->criticalBlocksUpdateAvailable($blocksUpdateInfo->newerReleases))
					$alerts[] = 'There is a critical update for Blocks available. <a href="'.UrlHelper::generateUrl('settings/updates').'">Update Now.</a>';
		}

		return $alerts;
	}
}
