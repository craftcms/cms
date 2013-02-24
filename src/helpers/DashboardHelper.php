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
				$alerts[] = Craft::t('The license key you’re using isn’t authorized to run CraftCMS on “{domain}”.', array('domain', craft()->request->serverName).' <a href="">'.Craft::t('Manage my licenses').'</a>');
			}

			if ($updateModel->craft->releases !== null && count($updateModel->craft->releases) > 0)
			{
				if (craft()->updates->criticalCraftUpdateAvailable($updateModel->craft->releases))
				{
					$alerts[] = Craft::t('There is a critical update for CraftCMS available.').' <a class="go" href="'.UrlHelper::getUrl('updates').'">'.Craft::t('Go to Updates').'</a>';
				}
			}

			return $alerts;
		}
	}
}
