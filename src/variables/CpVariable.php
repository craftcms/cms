<?php
namespace Craft;

/**
 * CP functions
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
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
	public function nav($iconSize = 32)
	{
		$nav['dashboard'] = array('label' => Craft::t('Dashboard'), 'icon' => 'gauge');

		if (craft()->sections->getTotalEditableSections())
		{
			$nav['entries'] = array('label' => Craft::t('Entries'), 'icon' => 'section');
		}

		$globals = craft()->globals->getEditableSets();

		if ($globals)
		{
			$nav['globals'] = array('label' => Craft::t('Globals'), 'url' => 'globals/'.$globals[0]->handle, 'icon' => 'globe');
		}

		if (craft()->categories->getEditableGroupIds())
		{
			$nav['categories'] = array('label' => Craft::t('Categories'), 'icon' => 'categories');
		}

		if (craft()->assetSources->getTotalViewableSources())
		{
			$nav['assets'] = array('label' => Craft::t('Assets'), 'icon' => 'assets');
		}

		if (craft()->getEdition() == Craft::Pro && craft()->userSession->checkPermission('editUsers'))
		{
			$nav['users'] = array('label' => Craft::t('Users'), 'icon' => 'users');
		}

		// Add any Plugin nav items
		$plugins = craft()->plugins->getPlugins();

		foreach ($plugins as $plugin)
		{
			if ($plugin->hasCpSection())
			{
				$pluginHandle = $plugin->getClassHandle();

				if (craft()->userSession->checkPermission('accessPlugin-'.$pluginHandle))
				{
					$lcHandle = StringHelper::toLowerCase($pluginHandle);
					$iconPath = craft()->path->getPluginsPath().$lcHandle.'/resources/icon-mask.svg';

					if (IOHelper::fileExists($iconPath))
					{
						$iconSvg = IOHelper::getFileContents($iconPath);
					}
					else
					{
						$iconSvg = false;
					}

					$nav[$lcHandle] = array(
						'label' => $plugin->getName(),
						'iconSvg' => $iconSvg
					);
				}
			}
		}

		if (craft()->userSession->isAdmin())
		{
			$nav['settings'] = array('label' => Craft::t('Settings'), 'icon' => 'settings');
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
	public function settings($iconSize = 32)
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

		$label = Craft::t('Plugins');

		foreach (craft()->plugins->getPlugins() as $plugin)
		{
			if ($plugin->hasSettings())
			{
				$pluginHandle = $plugin->getClassHandle();

				// Is this plugin managing its own settings?
				$settingsUrl = $plugin->getSettingsUrl();

				if (!$settingsUrl)
				{
					$settingsUrl = 'settings/plugins/'.StringHelper::toLowerCase($pluginHandle);
				}

				$settings[$label][$pluginHandle] = array(
					'url' => $settingsUrl,
					'iconUrl' => craft()->plugins->getPluginIconUrl($pluginHandle, $iconSize),
					'label' => $plugin->name
				);
			}
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
