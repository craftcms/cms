<?php
namespace Blocks;

/**
 * Handles plugin administration tasks
 */
class PluginsController extends BaseController
{
	/**
	 * Installs a plugin
	 */
	public function actionInstallPlugin()
	{
		$this->requirePostRequest();
		$className = blx()->request->getRequiredPost('pluginClass');

		if (blx()->plugins->installPlugin($className))
			blx()->user->setMessage(MessageType::Notice, 'Plugin installed.');
		else
			blx()->user->setMessage(MessageType::Error, 'Couldn’t install plugin.');

		$this->redirectToPostedUrl();
	}

	/**
	 * Uninstalls a plugin
	 */
	public function actionUninstallPlugin()
	{
		$this->requirePostRequest();
		$className = blx()->request->getRequiredPost('pluginClass');

		if (blx()->plugins->uninstallPlugin($className))
			blx()->user->setMessage(MessageType::Notice, 'Plugin uninstalled.');
		else
			blx()->user->setMessage(MessageType::Error, 'Couldn’t uninstall plugin.');

		$this->redirectToPostedUrl();
	}

	/**
	 * Enables a plugin
	 */
	public function actionEnablePlugin()
	{
		$this->requirePostRequest();
		$className = blx()->request->getRequiredPost('pluginClass');

		if (blx()->plugins->enablePlugin($className))
			blx()->user->setMessage(MessageType::Notice, 'Plugin enabled.');
		else
			blx()->user->setMessage(MessageType::Error, 'Couldn’t enable plugin.');

		$this->redirectToPostedUrl();
	}

	/**
	 * Disables a plugin
	 */
	public function actionDisablePlugin()
	{
		$this->requirePostRequest();
		$className = blx()->request->getRequiredPost('pluginClass');

		if (blx()->plugins->disablePlugin($className))
			blx()->user->setMessage(MessageType::Notice, 'Plugin disabled.');
		else
			blx()->user->setMessage(MessageType::Error, 'Couldn’t disable plugin.');

		$this->redirectToPostedUrl();
	}
}
