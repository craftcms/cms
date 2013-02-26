<?php
namespace Craft;

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

		if (craft()->updates->isUpdateInfoCached() || $fetch)
		{
			$updateModel = craft()->updates->getUpdates();

			if (craft()->et->getLicenseKeyStatus() == LicenseKeyStatus::InvalidKey)
			{
				$alerts[] = Craft::t('The license key you’re using isn’t authorized to run @@@appName@@@ on “{domain}”.', array('domain', craft()->request->serverName).' <a href="">'.Craft::t('Manage my licenses').'</a>');
			}

			if ($updateModel->app->releases !== null && count($updateModel->app->releases) > 0)
			{
				if (craft()->updates->criticalCraftUpdateAvailable($updateModel->app->releases))
				{
					$alerts[] = Craft::t('There is a critical update for @@@appName@@@ available.').' <a class="go" href="'.UrlHelper::getUrl('updates').'">'.Craft::t('Go to Updates').'</a>';
				}
			}

			return $alerts;
		}
	}
}
