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
		$user = craft()->userSession->getUser();

		if (!$user)
		{
			return $alerts;
		}

		if (craft()->updates->isUpdateInfoCached() || $fetch)
		{
			// Fetch the updates regardless of whether we're on the Updates page or not,
			// because the other alerts are relying on cached Elliott info
			$updateModel = craft()->updates->getUpdates();

			if ($path != 'updates' && $user->can('performUpdates'))
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
			$licenseKeyStatus = craft()->et->getLicenseKeyStatus();

			if ($licenseKeyStatus == LicenseKeyStatus::MismatchedDomain)
			{
				$licensedDomain = craft()->et->getLicensedDomain();
				$licenseKeyPath = craft()->path->getLicenseKeyPath();
				$licenseKeyFile = IOHelper::getFolderName($licenseKeyPath, false).'/'.IOHelper::getFileName($licenseKeyPath);

				$message = Craft::t('The license located at {file} belongs to {domain}.', array(
					'file'   => $licenseKeyFile,
					'domain' => '<a href="http://'.$licensedDomain.'" target="_blank">'.$licensedDomain.'</a>'
				));

				// Can they actually do something about it?
				if ($user->admin)
				{
					$action = '<a class="domain-mismatch">'.Craft::t('Transfer it to this domain?').'</a>';
				}
				else
				{
					$action = Craft::t('Please notify one of your site’s admins.');
				}

				$alerts[] = $message.' '.$action;
			}

			// Unlicensed packages?
			if ($path != 'settings/packages')
			{
				$licensedPackages = craft()->et->getLicensedPackages();
				$packageTrials    = craft()->et->getPackageTrials();

				// Could be false if not cached
				if (is_array($licensedPackages))
				{
					// Look for any unlicensed licenses
					$unlicensedPackages = array();

					foreach (craft()->getPackages() as $package)
					{
						if (!in_array($package, $licensedPackages))
						{
							// Make sure it's not in trial
							if (!is_array($packageTrials) || !in_array($package, array_keys($packageTrials)))
							{
								$unlicensedPackages[] = $package;
							}
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
						if ($user->admin)
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

				if ($packageTrials && $user->admin && !$user->hasShunned('packageTrialAlert'))
				{
					$expiringTrials = array();
					$currentTime = DateTimeHelper::currentUTCDateTime();
					$nextWeek = $currentTime->add(new DateInterval('P1W'))->getTimestamp();

					// See if there are any package trials that expire in less than a week
					foreach (craft()->getPackages() as $package)
					{
						if (!empty($packageTrials[$package]))
						{
							if ($packageTrials[$package] < $nextWeek)
							{
								$expiringTrials[] = $package;
							}
						}
					}

					if ($expiringTrials)
					{
						if (count($expiringTrials) == 1)
						{
							$message = Craft::t('Your {package} trial is expiring soon.', array('package' => Craft::t($expiringTrials[0])));
						}
						else
						{
							$message = Craft::t('You have multiple package trials expiring soon.');
						}

						$action1 = '<a class="shun:packageTrialAlert">'.Craft::t('Remind me later').'</a>';
						$action2 = '<a class="go" href="'.UrlHelper::getUrl('settings/packages').'">'.Craft::t('manage packages').'</a>';

						$alerts[] = $message.' '.$action1.' '.Craft::t('or').' '.$action2;
					}
				}
			}
		}

		return $alerts;
	}
}
