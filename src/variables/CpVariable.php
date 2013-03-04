<?php
namespace Craft;

/**
 * CP functions
 */
class CpVariable
{
	/**
	 * Get the sections of the CP.
	 *
	 * @return array
	 */
	public function nav()
	{
		$nav['dashboard'] = array('name' => Craft::t('Dashboard'));

		if (craft()->sections->getTotalEditableSections())
		{
			$nav['entries'] = array('name' => Craft::t('Entries'));
		}

		$nav['assets'] = array('name' => Craft::t('Assets'));

		if (craft()->globals->getTotalEditableSets())
		{
			$nav['globals'] = array('name' => Craft::t('Globals'));
		}

		if (Craft::hasPackage(CraftPackage::Users) && craft()->userSession->checkPermission('editUsers'))
		{
			$nav['users'] = array('name' => Craft::t('Users'));
		}

		// Add any Plugin nav items
		$plugins = craft()->plugins->getPlugins();

		foreach ($plugins as $plugin)
		{
			if ($plugin->hasCpSection())
			{
				if (craft()->userSession->checkPermission('accessPlugin-'.$plugin->getClassHandle()))
				{
					$lcHandle = strtolower($plugin->getClassHandle());
					$nav[$lcHandle] = array('name' => $plugin->getName());

					// Does the plugin have an icon?
					$resourcesPath = craft()->path->getPluginsPath().$lcHandle.'/resources/';

					if (IOHelper::fileExists($resourcesPath.'icon-16x16.png'))
					{
						$nav[$lcHandle]['hasIcon'] = true;

						$url = UrlHelper::getResourceUrl($lcHandle.'/icon-16x16.png');
						craft()->templates->includeCss("#sidebar #nav-{$lcHandle} { background-image: url('{$url}'); }");

						// Does it even have a hi-res version?
						if (IOHelper::fileExists($resourcesPath.'icon-32x32.png'))
						{
							$url = UrlHelper::getResourceUrl($lcHandle.'/icon-32x32.png');
							craft()->templates->includeHiResCss("#sidebar #nav-{$lcHandle} { background-image: url('{$url}'); }");
						}
					}
				}
			}
		}

		if (craft()->userSession->checkPermission('autoUpdateCraft'))
		{
			$totalAvailableUpdates = craft()->updates->getTotalAvailableUpdates();

			if ($totalAvailableUpdates > 0)
			{
				$nav['updates'] = array('name' => Craft::t('Updates'), 'badge' => $totalAvailableUpdates);
			}
			else
			{
				$nav['updates'] = array('name' => Craft::t('Updates'));
			}
		}

		if (craft()->userSession->isAdmin())
		{
			$nav['settings'] = array('name' => Craft::t('Settings'));
		}

		return $nav;
	}

	/**
	 * @return bool
	 */
	public function areAlertsCached()
	{
		return (craft()->et->getLicenseKeyStatus() !== false);
	}

	/**
	 * @return array
	 */
	public function getAlerts()
	{
		return CpHelper::getAlerts();
	}
}
