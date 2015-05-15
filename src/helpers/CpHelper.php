<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use Craft;
use craft\app\enums\LicenseKeyStatus;

/**
 * Class CpHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
		$alerts = [];
		$user = Craft::$app->getUser()->getIdentity();

		if (!$user)
		{
			return $alerts;
		}

		if (Craft::$app->getUpdates()->isUpdateInfoCached() || $fetch)
		{
			// Fetch the updates regardless of whether we're on the Updates page or not, because the other alerts are
			// relying on cached Elliott info
			$updateModel = Craft::$app->getUpdates()->getUpdates();

			if ($path != 'updates' && $user->can('performUpdates'))
			{
				if (!empty($updateModel->app->releases))
				{
					if (Craft::$app->getUpdates()->criticalCraftUpdateAvailable($updateModel->app->releases))
					{
						$alerts[] = Craft::t('app', 'There’s a critical Craft update available.') .
							' <a class="go nowrap" href="'.UrlHelper::getUrl('updates').'">'.Craft::t('app', 'Go to Updates').'</a>';
					}
				}
			}

			// Domain mismatch?
			$licenseKeyStatus = Craft::$app->getEt()->getLicenseKeyStatus();

			if ($licenseKeyStatus == LicenseKeyStatus::MismatchedDomain)
			{
				$licensedDomain = Craft::$app->getEt()->getLicensedDomain();
				$licenseKeyPath = Craft::$app->getPath()->getLicenseKeyPath();
				$licenseKeyFile = IOHelper::getFolderName($licenseKeyPath, false).'/'.IOHelper::getFilename($licenseKeyPath);

				$message = Craft::t('app', 'The license located at {file} belongs to {domain}.', [
					'file'   => $licenseKeyFile,
					'domain' => '<a href="http://'.$licensedDomain.'" target="_blank">'.$licensedDomain.'</a>'
				]);

				// Can they actually do something about it?
				if ($user->admin)
				{
					$action = '<a class="domain-mismatch">'.Craft::t('app', 'Transfer it to this domain?').'</a>';
				}
				else
				{
					$action = Craft::t('app', 'Please notify one of your site’s admins.');
				}

				$alerts[] = $message.' '.$action;
			}
		}

		return $alerts;
	}
}
