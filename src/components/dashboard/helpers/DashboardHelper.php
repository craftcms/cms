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

		if (blx()->updates->isUpdateInfoCached() || $fetch)
		{
			$updateModel = blx()->updates->getUpdates();

			if (blx()->et->getLicenseKeyStatus() == LicenseKeyStatus::InvalidKey)
			{
				$alerts[] = Blocks::t('The license key you’re using isn’t authorized to run Blocks on “{domain}”.', array('domain', blx()->request->serverName).' <a href="">'.Blocks::t('Manage my licenses').'</a>');
			}

			if ($updateModel->blocks->releases !== null && count($updateModel->blocks->releases) > 0)
			{
				if (blx()->updates->criticalBlocksUpdateAvailable($updateModel->blocks->releases))
				{
					$alerts[] = Blocks::t('There is a critical update for Blocks available.').' <a class="go" href="'.UrlHelper::getUrl('updates').'">'.Blocks::t('Go to Updates').'</a>';
				}
			}

			return $alerts;
		}
	}
}
