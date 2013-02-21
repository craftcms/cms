<?php
namespace Blocks;

/**
 * Handles plugin administration tasks
 */
class PluginsController extends BaseController
{
	/**
	 * Installs a plugin.
	 */
	public function actionInstallPlugin()
	{
		$this->requirePostRequest();
		$className = blx()->request->getRequiredPost('pluginClass');

		if (blx()->plugins->installPlugin($className))
		{
			blx()->userSession->setNotice(Blocks::t('Plugin installed.'));
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t install plugin.'));
		}

		$this->redirectToPostedUrl();
	}

	/**
	 * Uninstalls a plugin.
	 */
	public function actionUninstallPlugin()
	{
		$this->requirePostRequest();
		$className = blx()->request->getRequiredPost('pluginClass');

		if (blx()->plugins->uninstallPlugin($className))
		{
			blx()->userSession->setNotice(Blocks::t('Plugin uninstalled.'));
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t uninstall plugin.'));
		}

		$this->redirectToPostedUrl();
	}

	/**
	 * Enables a plugin.
	 */
	public function actionEnablePlugin()
	{
		$this->requirePostRequest();
		$className = blx()->request->getRequiredPost('pluginClass');

		if (blx()->plugins->enablePlugin($className))
		{
			blx()->userSession->setNotice(Blocks::t('Plugin enabled.'));
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t enable plugin.'));
		}

		$this->redirectToPostedUrl();
	}

	/**
	 * Disables a plugin.
	 */
	public function actionDisablePlugin()
	{
		$this->requirePostRequest();
		$className = blx()->request->getRequiredPost('pluginClass');

		if (blx()->plugins->disablePlugin($className))
		{
			blx()->userSession->setNotice(Blocks::t('Plugin disabled.'));
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t disable plugin.'));
		}

		$this->redirectToPostedUrl();
	}

	/**
	 * Saves a plugin's settings.
	 */
	public function actionSavePluginSettings()
	{
		$this->requirePostRequest();
		$pluginClass = blx()->request->getRequiredPost('pluginClass');
		$settings = blx()->request->getPost('settings');

		$plugin = blx()->plugins->getPlugin($pluginClass);
		if (!$plugin)
		{
			throw new Exception(Blocks::t('No plugin exists with the class “{class}”', array('class' => $pluginClass)));
		}

		if (blx()->plugins->savePluginSettings($plugin, $settings))
		{
			blx()->userSession->setNotice(Blocks::t('Plugin settings saved.'));

			$this->redirectToPostedUrl();
		}
		else
		{
			$plugin->setSettings($settings);

			blx()->userSession->setError(Blocks::t(Blocks::t('Couldn’t save plugin settings.')));

			$this->renderRequestedTemplate(array(
				'plugin' => $plugin
			));
		}
	}
}
