<?php
namespace Craft;

/**
 * CP functions
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.variables
 * @since     1.0
 */
class CpVariable
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
		$nav['dashboard'] = array('label' => Craft::t('Dashboard'));

		if (craft()->sections->getTotalEditableSections())
		{
			$nav['entries'] = array('label' => Craft::t('Entries'));
		}

		$globals = craft()->globals->getEditableSets();

		if ($globals)
		{
			$nav['globals'] = array('label' => Craft::t('Globals'), 'url' => 'globals/'.$globals[0]->handle);
		}

		if (craft()->categories->getEditableGroupIds())
		{
			$nav['categories'] = array('label' => Craft::t('Categories'));
		}

		if (craft()->assetSources->getTotalViewableSources())
		{
			$nav['assets'] = array('label' => Craft::t('Assets'));
		}

		if (craft()->getEdition() == Craft::Pro && craft()->userSession->checkPermission('editUsers'))
		{
			$nav['users'] = array('label' => Craft::t('Users'));
		}

		// Add any Plugin nav items
		$plugins = craft()->plugins->getPlugins();

		foreach ($plugins as $plugin)
		{
			if ($plugin->hasCpSection())
			{
				if (craft()->userSession->checkPermission('accessPlugin-'.$plugin->getClassHandle()))
				{
					$lcHandle = StringHelper::toLowerCase($plugin->getClassHandle());
					$nav[$lcHandle] = array('label' => $plugin->getName());
				}
			}
		}

		// Allow plugins to modify the nav
		craft()->plugins->call('modifyCpNav', array(&$nav));

		// Figure out which item is selected, and normalize the items
		$firstSegment = craft()->request->getSegment(1);

		if ($firstSegment == 'myaccount')
		{
			$firstSegment = 'users';
		}

		foreach ($nav as $handle => &$item)
		{
			if (is_string($item))
			{
				$item = array('label' => $item);
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
		$label = Craft::t('System');

		$settings[$label]['general'] = array('icon' => 'general', 'label' => Craft::t('General'));
		$settings[$label]['routes'] = array('icon' => 'routes', 'label' => Craft::t('Routes'));

		if (craft()->getEdition() == Craft::Pro)
		{
			$settings[$label]['users'] = array('icon' => 'users', 'label' => Craft::t('Users'));
		}

		$settings[$label]['email'] = array('icon' => 'mail', 'label' => Craft::t('Email'));
		$settings[$label]['plugins'] = array('icon' => 'plugin', 'label' => Craft::t('Plugins'));

		$label = Craft::t('Content');

		$settings[$label]['fields'] = array('icon' => 'field', 'label' => Craft::t('Fields'));
		$settings[$label]['sections'] = array('icon' => 'section', 'label' => Craft::t('Sections'));
		$settings[$label]['assets'] = array('icon' => 'assets', 'label' => Craft::t('Assets'));
		$settings[$label]['globals'] = array('icon' => 'globe', 'label' => Craft::t('Globals'));
		$settings[$label]['categories'] = array('icon' => 'categories', 'label' => Craft::t('Categories'));
		$settings[$label]['tags'] = array('icon' => 'tags', 'label' => Craft::t('Tags'));

		if (craft()->getEdition() == Craft::Pro)
		{
			$settings[$label]['locales'] = array('icon' => 'language', 'label' => Craft::t('Locales'));
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
		return (craft()->et->getLicenseKeyStatus() !== false);
	}

	/**
	 * Returns an array of alerts to display in the CP.
	 *
	 * @return array
	 */
	public function getAlerts()
	{
		return CpHelper::getAlerts(craft()->request->getPath());
	}
}
