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
	public static function getAlerts($path = null, $fetch = false)
	{
		$alerts = array();

		if (craft()->updates->isUpdateInfoCached() || $fetch)
		{
			// Fetch the updates regardless of whether we're on the Updates page or not,
			// because the other alerts are relying on cached Elliott info
			$updateModel = craft()->updates->getUpdates();

			if ($path != 'updates' && craft()->userSession->checkPermission('performUpdates'))
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

			// Domain mismatch?
			if (craft()->userSession->isAdmin())
			{
				$licenseKeyStatus = craft()->et->getLicenseKeyStatus();

				if ($licenseKeyStatus == LicenseKeyStatus::MismatchedDomain)
				{
					$licensedDomain = craft()->et->getLicensedDomain();
					$licenseKeyPath = craft()->et->getLicenseKeyPath();
					$licenseKeyFile = IOHelper::getFolderName($licenseKeyPath, false).'/'.IOHelper::getFileName($licenseKeyPath);

					$message = Craft::t('The license located at {file} belongs to {domain}.', array(
						'file'   => $licenseKeyFile,
						'domain' => '<a href="http://'.$licensedDomain.'" target="_blank">'.$licensedDomain.'</a>'
					));

					$action = '<a class="domain-mismatch">'.Craft::t('Transfer it to this domain?').'</a>';

					$alerts[] = $message.' '.$action;
				}
			}

			// Unlicensed packages?
			if ($path != 'settings/packages')
			{
				$licensedPackages = craft()->et->getLicensedPackages();

				// Could be false!
				if (is_array($licensedPackages))
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

						// Can they actually do something about it?
						if (craft()->userSession->isAdmin())
						{
							$action = '<a class="go" href="'.UrlHelper::getUrl('settings/packages').'">'.Craft::t('Manage packages').'</a>';
						}
						else
						{
							$action = Craft::t('Please notify one of your site’s admins.');
						}

						$alerts[] = $message.' '.$action;
					}
				}
			}
		}

		return $alerts;
	}
}
