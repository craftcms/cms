<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\Exception;
use craft\app\errors\HttpException;

/**
 * The PluginsController class is a controller that handles various plugin related tasks such installing, uninstalling,
 * enabling, disabling and saving plugin settings in the control panel.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[BaseController::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PluginsController extends BaseController
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseController::init()
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function init()
	{
		// All plugin actions require an admin
		$this->requireAdmin();
	}

	/**
	 * Installs a plugin.
	 *
	 * @return null
	 */
	public function actionInstallPlugin()
	{
		$this->requirePostRequest();
		$className = Craft::$app->getRequest()->getRequiredBodyParam('pluginClass');

		if (Craft::$app->plugins->installPlugin($className))
		{
			Craft::$app->getSession()->setNotice(Craft::t('Plugin installed.'));
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('Couldn’t install plugin.'));
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
		$className = Craft::$app->getRequest()->getRequiredBodyParam('pluginClass');

		if (Craft::$app->plugins->uninstallPlugin($className))
		{
			Craft::$app->getSession()->setNotice(Craft::t('Plugin uninstalled.'));
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('Couldn’t uninstall plugin.'));
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
		$className = Craft::$app->getRequest()->getRequiredBodyParam('pluginClass');

		if (Craft::$app->plugins->enablePlugin($className))
		{
			Craft::$app->getSession()->setNotice(Craft::t('Plugin enabled.'));
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('Couldn’t enable plugin.'));
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
		$className = Craft::$app->getRequest()->getRequiredBodyParam('pluginClass');

		if (Craft::$app->plugins->disablePlugin($className))
		{
			Craft::$app->getSession()->setNotice(Craft::t('Plugin disabled.'));
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('Couldn’t disable plugin.'));
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
		$pluginClass = Craft::$app->getRequest()->getRequiredBodyParam('pluginClass');
		$settings = Craft::$app->getRequest()->getBodyParam('settings');

		$plugin = Craft::$app->plugins->getPlugin($pluginClass);
		if (!$plugin)
		{
			throw new Exception(Craft::t('No plugin exists with the class “{class}”', ['class' => $pluginClass]));
		}

		if (Craft::$app->plugins->savePluginSettings($plugin, $settings))
		{
			Craft::$app->getSession()->setNotice(Craft::t('Plugin settings saved.'));

			$this->redirectToPostedUrl();
		}

		Craft::$app->getSession()->setError(Craft::t('Couldn’t save plugin settings.'));

		// Send the plugin back to the template
		Craft::$app->getUrlManager()->setRouteVariables([
			'plugin' => $plugin
		]);
	}
}
