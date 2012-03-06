<?php
namespace Blocks;

/**
 *
 */
class InstallController extends BaseController
{
	/**
	 * Init
	 */
	public function init()
	{
		// Return a 404 if Blocks is already installed
		if (!b()->config->getItem('devMode') && b()->isInstalled)
			throw new HttpException(404);
	}

	/**
	 * Index action
	 */
	public function actionIndex()
	{
		$reqCheck = new RequirementsChecker();
		$reqCheck->run();

		if ($reqCheck->result !== InstallStatus::Failure)
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
			b()->installer->run();

			$r = array('success' => true);
		}
		catch (Exception $e)
		{
			$r = array('error' => $e->getMessage());
		}

		$this->returnJson($r);
	}
}
