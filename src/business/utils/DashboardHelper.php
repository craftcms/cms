<?php
namespace Blocks;

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

		if (Blocks::app()->updates->isUpdateInfoCached() || $fetch)
		{
			$blocksUpdateInfo = Blocks::app()->updates->updateInfo;

			if (Blocks::app()->sites->licenseKeyStatus == LicenseKeyStatus::InvalidKey)
				$alerts[] = 'The license key you’re using isn’t authorized to run Blocks '.Blocks::getEdition().' on '.Blocks::app()->request->serverName.'. <a href="">Manage my licenses</a>';

			if ($blocksUpdateInfo->newerReleases !== null && count($blocksUpdateInfo->newerReleases) > 0)
				if (Blocks::app()->updates->criticalBlocksUpdateAvailable($blocksUpdateInfo->newerReleases))
					$alerts[] = 'There is a critical update for Blocks available. <a href="'.UrlHelper::generateUrl('settings/updates').'">Update Now.</a>';
		}

		return $alerts;
	}
}
