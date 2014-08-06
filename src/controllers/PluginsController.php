<?php
namespace Craft;

/**
 * Handles plugin administration tasks.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class PluginsController extends BaseController
{
	////////////////////
	// PUBLIC METHODS
	////////////////////

	/**
	 * Installs a plugin.
	 *
	 * @return null
	 */
	public function actionInstallPlugin()
	{
		$this->requirePostRequest();
		$className = craft()->request->getRequiredPost('pluginClass');

		if (craft()->plugins->installPlugin($className))
		{
			craft()->userSession->setNotice(Craft::t('Plugin installed.'));
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t install plugin.'));
		}

		$this->redirectToPostedUrl();
	}

	/**
	 * Uninstalls a plugin.
	 *
	 * @return null
	 */
	public function actionUninstallPlugin()
	{
		$this->requirePostRequest();
		$className = craft()->request->getRequiredPost('pluginClass');

		if (craft()->plugins->uninstallPlugin($className))
		{
			craft()->userSession->setNotice(Craft::t('Plugin uninstalled.'));
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t uninstall plugin.'));
		}

		$this->redirectToPostedUrl();
	}

	/**
	 * Enables a plugin.
	 *
	 * @return null
	 */
	public function actionEnablePlugin()
	{
		$this->requirePostRequest();
		$className = craft()->request->getRequiredPost('pluginClass');

		if (craft()->plugins->enablePlugin($className))
		{
			craft()->userSession->setNotice(Craft::t('Plugin enabled.'));
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t enable plugin.'));
		}

		$this->redirectToPostedUrl();
	}

	/**
	 * Disables a plugin.
	 *
	 * @return null
	 */
	public function actionDisablePlugin()
	{
		$this->requirePostRequest();
		$className = craft()->request->getRequiredPost('pluginClass');

		if (craft()->plugins->disablePlugin($className))
		{
			craft()->userSession->setNotice(Craft::t('Plugin disabled.'));
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t disable plugin.'));
		}

		$this->redirectToPostedUrl();
	}

	/**
	 * Saves a plugin's settings.
	 *
	 * @throws Exception
	 * @return null
	 */
	public function actionSavePluginSettings()
	{
		$this->requirePostRequest();
		$pluginClass = craft()->request->getRequiredPost('pluginClass');
		$settings = craft()->request->getPost('settings');

		$plugin = craft()->plugins->getPlugin($pluginClass);
		if (!$plugin)
		{
			throw new Exception(Craft::t('No plugin exists with the class “{class}”', array('class' => $pluginClass)));
		}

		if (craft()->plugins->savePluginSettings($plugin, $settings))
		{
			craft()->userSession->setNotice(Craft::t('Plugin settings saved.'));

			$this->redirectToPostedUrl();
		}

		craft()->userSession->setError(Craft::t('Couldn’t save plugin settings.'));

		// Send the plugin back to the template
		craft()->urlManager->setRouteVariables(array(
			'plugin' => $plugin
		));
	}
}
