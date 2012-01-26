<?php

/**
 *
 */
class bInstallController extends bBaseController
{
	public function init()
	{
		// Return a 404 if Blocks is already installed
		if (!Blocks::app()->getConfig('devMode') && Blocks::app()->install->isBlocksInstalled)
			throw new bHttpException(404);
	}

	/**
	 */
	public function actionIndex()
	{
		$reqCheck = new bRequirementsChecker();
		$reqCheck->run();

		if ($reqCheck->result !== bInstallStatus::Failure)
			$this->loadTemplate('install');
		else
			$this->loadTemplate('install/cantinstall', array('requirements' => $reqCheck->requirements));
	}

	public function actionInstall()
	{
		// This must be a POST request
		$this->requirePostRequest();

		// Run the installer
		Blocks::app()->install->installBlocks();

		// TODO: redirect to the setup wizard
		die('Blocks is installed!');
	}
}
