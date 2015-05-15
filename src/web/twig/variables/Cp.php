<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\variables;

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
		$nav['dashboard'] = ['label' => \Craft::t('app', 'Dashboard')];

		if (\Craft::$app->getSections()->getTotalEditableSections())
		{
			$nav['entries'] = ['label' => \Craft::t('app', 'Entries')];
		}

		$globals = \Craft::$app->getGlobals()->getEditableSets();

		if ($globals)
		{
			$nav['globals'] = ['label' => \Craft::t('app', 'Globals'), 'url' => 'globals/'.$globals[0]->handle];
		}

		if (\Craft::$app->getCategories()->getEditableGroupIds())
		{
			$nav['categories'] = ['label' => \Craft::t('app', 'Categories')];
		}

		if (\Craft::$app->getVolumes()->getTotalViewablevolumes())
		{
			$nav['assets'] = ['label' => \Craft::t('app', 'Assets')];
		}

		if (\Craft::$app->getEdition() == \Craft::Pro && \Craft::$app->getUser()->checkPermission('editUsers'))
		{
			$nav['users'] = ['label' => \Craft::t('app', 'Users')];
		}

		// Add any Plugin nav items
		$plugins = \Craft::$app->getPlugins()->getAllPlugins();

		foreach ($plugins as $plugin)
		{
			if ($plugin::hasCpSection())
			{
				$handle = $plugin->getHandle();
				if (\Craft::$app->getUser()->checkPermission('accessPlugin-'.$handle))
				{
					$nav[$handle] = ['label' => $plugin->name];
				}
			}
		}

		// Allow plugins to modify the nav
		\Craft::$app->getPlugins()->call('modifyCpNav', [&$nav]);

		// Figure out which item is selected, and normalize the items
		$firstSegment = \Craft::$app->getRequest()->getSegment(1);

		if ($firstSegment == 'myaccount')
		{
			$firstSegment = 'users';
		}

		foreach ($nav as $handle => &$item)
		{
			if (is_string($item))
			{
				$item = ['label' => $item];
			}

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
		$label = \Craft::t('app', 'System');

		$settings[$label]['general'] = ['icon' => 'general', 'label' => \Craft::t('app', 'General')];
		$settings[$label]['routes'] = ['icon' => 'routes', 'label' => \Craft::t('app', 'Routes')];

		if (\Craft::$app->getEdition() == \Craft::Pro)
		{
			$settings[$label]['users'] = ['icon' => 'users', 'label' => \Craft::t('app', 'Users')];
		}

		$settings[$label]['email'] = ['icon' => 'mail', 'label' => \Craft::t('app', 'Email')];
		$settings[$label]['plugins'] = ['icon' => 'plugin', 'label' => \Craft::t('app', 'Plugins')];

		$label = \Craft::t('app', 'Content');

		$settings[$label]['fields'] = ['icon' => 'field', 'label' => \Craft::t('app', 'Fields')];
		$settings[$label]['sections'] = ['icon' => 'section', 'label' => \Craft::t('app', 'Sections')];
		$settings[$label]['assets'] = ['icon' => 'assets', 'label' => \Craft::t('app', 'Assets')];
		$settings[$label]['globals'] = ['icon' => 'globe', 'label' => \Craft::t('app', 'Globals')];
		$settings[$label]['categories'] = ['icon' => 'categories', 'label' => \Craft::t('app', 'Categories')];
		$settings[$label]['tags'] = ['icon' => 'tags', 'label' => \Craft::t('app', 'Tags')];

		if (\Craft::$app->getEdition() == \Craft::Pro)
		{
			$settings[$label]['locales'] = ['icon' => 'language', 'label' => \Craft::t('app', 'Locales')];
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
		return (\Craft::$app->getEt()->getLicenseKeyStatus() !== false);
	}

	/**
	 * Returns an array of alerts to display in the CP.
	 *
	 * @return array
	 */
	public function getAlerts()
	{
		return CpHelper::getAlerts(\Craft::$app->getRequest()->getPathInfo());
	}
}
