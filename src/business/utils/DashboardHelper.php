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

		if (blx()->updates->getIsUpdateInfoCached() || $fetch)
		{
			$updateInfo = blx()->updates->getUpdateInfo();

			if (blx()->et->getLicenseKeyStatus() == LicenseKeyStatus::InvalidKey)
				$alerts[] = Blocks::t('The license key you’re using isn’t authorized to run @@@productDisplay@@@ on “{serverName}”.', array('serverName', blx()->request->serverName).' <a href="">'.Blocks::t('Manage my licenses').'</a>');

			if ($updateInfo->blocks->releases !== null && count($updateInfo->blocks->releases) > 0)
				if (blx()->updates->criticalBlocksUpdateAvailable($updateInfo->blocks->releases))
					$alerts[] = Blocks::t('There is a critical update for @@@productDisplay@@@ available.').' <a class="go" href="'.UrlHelper::generateUrl('updates').'">'.Blocks::t('Go to Updates').'</a>';
		}

		return $alerts;
	}
}
