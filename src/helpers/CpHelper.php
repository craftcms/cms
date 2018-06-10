<?php
namespace Craft;

/**
 * Class CpHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class CpHelper
{
	// Public Methods
	// =========================================================================

	/**
	 * @param string|null $path
	 * @param bool        $fetch
	 *
	 * @return array
	 */
	public static function getAlerts($path = null, $fetch = false)
	{
		$alerts = array();
		$user = craft()->userSession->getUser();

		if (!$user)
		{
			return $alerts;
		}

		if (craft()->updates->isUpdateInfoCached() || $fetch)
		{
			// Fetch the updates regardless of whether we're on the Updates page or not, because the other alerts are
			// relying on cached Elliott info
			$updateModel = craft()->updates->getUpdates();

			// Get the license key status
			$licenseKeyStatus = craft()->et->getLicenseKeyStatus();

			// Invalid license?
			if ($licenseKeyStatus == LicenseKeyStatus::Invalid)
			{
				$alerts[] = Craft::t('Your license key is invalid.');
			}
			else if (!craft()->canTestEditions())
			{
				$edition = craft()->getEdition();
				$licensedEdition = craft()->getLicensedEdition();
				if ($licensedEdition !== null && $edition > $licensedEdition)
				{
					$alerts[] = Craft::t('You’re running Craft {edition} with a Craft {licensedEdition} license.', array(
							'edition' => craft()->getEditionName(),
							'licensedEdition' => craft()->getLicensedEditionName()
						)) .
						' <a class="go edition-resolution">'.Craft::t('Resolve').'</a>';
				}
			}

			if ($path != 'updates' && $user->can('performUpdates'))
			{
				if (!empty($updateModel->app->releases))
				{
					if (craft()->updates->criticalCraftUpdateAvailable($updateModel->app->releases))
					{
						$alerts[] = Craft::t('There’s a critical Craft CMS update available.') .
							' <a class="go nowrap" href="'.UrlHelper::getUrl('updates').'">'.Craft::t('Go to Updates').'</a>';
					}
				}
			}

			// Domain mismatch?
			if ($licenseKeyStatus == LicenseKeyStatus::Mismatched)
			{
				$licensedDomain = craft()->et->getLicensedDomain();
				$licenseKeyPath = craft()->path->getLicenseKeyPath();
				$licenseKeyFile = IOHelper::getFolderName($licenseKeyPath, false).'/'.IOHelper::getFileName($licenseKeyPath);

				$alerts[] = Craft::t('The license located at {file} belongs to {domain}.', array(
						'file' => $licenseKeyFile,
						'domain' => '<a href="http://'.$licensedDomain.'" target="_blank">'.$licensedDomain.'</a>'
					)).
					' <a class="go" href="https://craftcms.com/support/resolving-mismatched-licenses">'.Craft::t('Learn more').'</a>';
			}
		}

		$allPluginAlerts = craft()->plugins->call('getCpAlerts', array($path, $fetch), true);

		foreach ($allPluginAlerts as $pluginAlerts)
		{
			$alerts = array_merge($alerts, $pluginAlerts);
		}

		return $alerts;
	}
}
