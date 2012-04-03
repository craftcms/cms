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
	 */
	public static function getAlerts($fetch = false)
	{
		$alerts = array();

		if (b()->updates->isUpdateInfoCached() || $fetch)
		{
			$updateInfo = b()->updates->updateInfo;

			if (b()->sites->licenseKeyStatus == LicenseKeyStatus::InvalidKey)
				$alerts[] = 'The license key you’re using isn’t authorized to run Blocks '.Blocks::getEdition().' on '.b()->request->serverName.'. <a href="">Manage my licenses</a>';

			if ($updateInfo->blocks->releases !== null && count($updateInfo->blocks->releases) > 0)
				if (b()->updates->criticalBlocksUpdateAvailable($updateInfo->blocks->releases))
					$alerts[] = 'There is a critical update for Blocks available. <a class="go" href="'.UrlHelper::generateUrl('settings/updates').'">Go to Updates</a>';
		}

		return $alerts;
	}
}
