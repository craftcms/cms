<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use Craft;
use craft\app\helpers\CpHelper;
use craft\app\helpers\StringHelper;
use craft\app\helpers\UrlHelper;

/**
 * CP functions
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Cp
{
	// Public Methods
	// =========================================================================

	/**
	 * Get the sections of the CP.
	 *
	 * @return array
	 */
	public function nav()
	{
		$nav['dashboard'] = ['name' => Craft::t('Dashboard')];

		if (Craft::$app->sections->getTotalEditableSections())
		{
			$nav['entries'] = ['name' => Craft::t('Entries')];
		}

		$globals = Craft::$app->globals->getEditableSets();

		if ($globals)
		{
			$nav['globals'] = ['name' => Craft::t('Globals'), 'url' => 'globals/'.$globals[0]->handle];
		}

		if (Craft::$app->categories->getEditableGroupIds())
		{
			$nav['categories'] = ['name' => Craft::t('Categories')];
		}

		if (Craft::$app->assetSources->getTotalViewableSources())
		{
			$nav['assets'] = ['name' => Craft::t('Assets')];
		}

		if (Craft::$app->getEdition() == Craft::Pro && Craft::$app->getUser()->checkPermission('editUsers'))
		{
			$nav['users'] = ['name' => Craft::t('Users')];
		}

		// Add any Plugin nav items
		$plugins = Craft::$app->plugins->getPlugins();

		foreach ($plugins as $plugin)
		{
			if ($plugin->hasCpSection())
			{
				if (Craft::$app->getUser()->checkPermission('accessPlugin-'.$plugin->getClassHandle()))
				{
					$lcHandle = StringHelper::toLowerCase($plugin->getClassHandle());
					$nav[$lcHandle] = ['name' => $plugin->getName()];
				}
			}
		}

		$firstSegment = Craft::$app->request->getSegment(1);

		if ($firstSegment == 'myaccount')
		{
			$firstSegment = 'users';
		}

		foreach ($nav as $handle => &$item)
		{
			$item['sel'] = ($handle == $firstSegment);

			if (isset($item['url']))
			{
				$item['url'] = UrlHelper::getUrl($item['url']);
			}
			else
			{
				$item['url'] = UrlHelper::getUrl($handle);
			}
		}

		return $nav;
	}

	/**
	 * Returns the list of settings.
	 *
	 * @return array
	 */
	public function settings()
	{
		$label = Craft::t('System');

		$settings[$label]['general'] = ['icon' => 'general', 'label' => Craft::t('General')];
		$settings[$label]['routes'] = ['icon' => 'routes', 'label' => Craft::t('Routes')];

		if (Craft::$app->getEdition() == Craft::Pro)
		{
			$settings[$label]['users'] = ['icon' => 'users', 'label' => Craft::t('Users')];
		}

		$settings[$label]['email'] = ['icon' => 'mail', 'label' => Craft::t('Email')];
		$settings[$label]['plugins'] = ['icon' => 'plugin', 'label' => Craft::t('Plugins')];

		$label = Craft::t('Content');

		$settings[$label]['fields'] = ['icon' => 'field', 'label' => Craft::t('Fields')];
		$settings[$label]['sections'] = ['icon' => 'section', 'label' => Craft::t('Sections')];
		$settings[$label]['assets'] = ['icon' => 'assets', 'label' => Craft::t('Assets')];
		$settings[$label]['globals'] = ['icon' => 'globe', 'label' => Craft::t('Globals')];
		$settings[$label]['categories'] = ['icon' => 'categories', 'label' => Craft::t('Categories')];
		$settings[$label]['tags'] = ['icon' => 'tags', 'label' => Craft::t('Tags')];

		if (Craft::$app->getEdition() == Craft::Pro)
		{
			$settings[$label]['locales'] = ['icon' => 'language', 'label' => Craft::t('Locales')];
		}

		return $settings;
	}

	/**
	 * Returns whether the CP alerts are cached.
	 *
	 * @return bool
	 */
	public function areAlertsCached()
	{
		// The license key status gets cached on each Elliott request
		return (Craft::$app->et->getLicenseKeyStatus() !== false);
	}

	/**
	 * Returns an array of alerts to display in the CP.
	 *
	 * @return array
	 */
	public function getAlerts()
	{
		return CpHelper::getAlerts(Craft::$app->request->getPath());
	}
}
