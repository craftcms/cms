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

		if (b()->updates->isUpdateInfoCached() || $fetch)
		{
			$blocksUpdateInfo = b()->updates->updateInfo;

			if (b()->sites->licenseKeyStatus == LicenseKeyStatus::InvalidKey)
				$alerts[] = 'The license key you’re using isn’t authorized to run Blocks '.Blocks::getEdition().' on '.b()->request->serverName.'. <a href="">Manage my licenses</a>';

			if ($blocksUpdateInfo->newerReleases !== null && count($blocksUpdateInfo->newerReleases) > 0)
				if (b()->updates->criticalBlocksUpdateAvailable($blocksUpdateInfo->newerReleases))
					$alerts[] = 'There is a critical update for Blocks available. <a class="go" href="'.UrlHelper::generateUrl('settings/updates').'">Go to Updates</a>';
		}

		return $alerts;
	}
}
