<?php

/**
 *
 */
class bInstallController extends bBaseController
{
	/**
	 * Init
	 */
	public function init()
	{
		// Return a 404 if Blocks is already installed
		if (!Blocks::app()->getConfig('devMode') && Blocks::app()->isInstalled)
			throw new bHttpException(404);
	}

	/**
	 * Index action
	 */
	public function actionIndex()
	{
		$reqCheck = new bRequirementsChecker();
		$reqCheck->run();

		if ($reqCheck->result !== bInstallStatus::Failure)
			$this->loadTemplate('_special/install');
		else
			$this->loadTemplate('_special/install/cantinstall', array('requirements' => $reqCheck->requirements));
	}

	/**
	 * Install action
	 */
	public function actionInstall()
	{
		// This must be a POST and Ajax request
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		// Run the installer
		try
		{
			Blocks::app()->installer->run();

			$r = array('success' => true);
		}
		catch (Exception $e)
		{
			$r = array('error' => $e->getMessage());
		}

		bJson::sendJsonHeaders();
		echo bJson::encode($r);
	}
}
