<?php
namespace Craft;

/**
 *
 */
class CpHelper
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
			$updateModel      = craft()->updates->getUpdates();
			$licenseKeyStatus = craft()->et->getLicenseKeyStatus();
			$licensedPackages = craft()->et->getLicensedPackages();

			$path = craft()->request->getPath();

			if ($path != 'updates')
			{
				if (!empty($updateModel->app->releases))
				{
					if (craft()->updates->criticalCraftUpdateAvailable($updateModel->app->releases))
					{
						$alerts[] = Craft::t('There’s a critical @@@appName@@@ update available.') .
							' <a class="go" href="'.UrlHelper::getUrl('updates').'">'.Craft::t('Go to Updates').'</a>';
					}
				}
			}

			if ($path != 'resolvelicense')
			{
				if ($licenseKeyStatus == LicenseKeyStatus::MismatchedDomain)
				{
					$alerts[] = Craft::t('Your @@@appName@@@ license is associated with a different domain.') .
						' <a class="go" href="'.UrlHelper::getUrl('resolvelicense').'">'.Craft::t('Resolve').'</a>';
				}
			}

			if ($path != 'settings/packages')
			{
				// Look for any unlicensed licenses
				$unlicensedPackages = array();

				foreach (Craft::getPackages() as $package)
				{
					if (!in_array($package, $licensedPackages))
					{
						$unlicensedPackages[] = $package;
					}
				}

				if ($unlicensedPackages)
				{
					if (count($unlicensedPackages) == 1)
					{
						$message = Craft::t('The {package} package is installed, but it’s not licensed.', array('package' => Craft::t($unlicensedPackages[0])));
					}
					else
					{
						$message = Craft::t('You have multiple unlicensed packages installed.');
					}

					$alerts[] = $message.' <a class="go" href="'.UrlHelper::getUrl('settings/packages').'">'.Craft::t('Manage packages').'</a>';
				}
			}
		}

		return $alerts;
	}
}
