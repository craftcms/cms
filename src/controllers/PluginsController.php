<?php
namespace Blocks;

/**
 * Handles plugin administration tasks
 */
class PluginsController extends Controller
{
	/**
	 * Installs a plugin
	 */
	public function actionInstallPlugin()
	{
		$this->requirePostRequest();
		$className = b()->request->getRequiredPost('pluginClass');
		if (b()->plugins->installPlugin($className))
			b()->user->setMessage(MessageType::Notice, 'Plugin installed.');
		else
			b()->user->setMessage(MessageType::Error, 'Couldn’t install plugin.');
		$this->redirectToPostedUrl();
	}

	/**
	 * Uninstalls a plugin
	 */
	public function actionUninstallPlugin()
	{
		$this->requirePostRequest();
		$className = b()->request->getRequiredPost('pluginClass');
		if (b()->plugins->uninstallPlugin($className))
			b()->user->setMessage(MessageType::Notice, 'Plugin uninstalled.');
		else
			b()->user->setMessage(MessageType::Error, 'Couldn’t uninstall plugin.');
		$this->redirectToPostedUrl();
	}

	/**
	 * Enables a plugin
	 */
	public function actionEnablePlugin()
	{
		$this->requirePostRequest();
		$className = b()->request->getRequiredPost('pluginClass');
		if (b()->plugins->enablePlugin($className))
			b()->user->setMessage(MessageType::Notice, 'Plugin enabled.');
		else
			b()->user->setMessage(MessageType::Error, 'Couldn’t enable plugin.');
		$this->redirectToPostedUrl();
	}

	/**
	 * Disables a plugin
	 */
	public function actionDisablePlugin()
	{
		$this->requirePostRequest();
		$className = b()->request->getRequiredPost('pluginClass');
		if (b()->plugins->disablePlugin($className))
			b()->user->setMessage(MessageType::Notice, 'Plugin disabled.');
		else
			b()->user->setMessage(MessageType::Error, 'Couldn’t disable plugin.');
		$this->redirectToPostedUrl();
	}
}
