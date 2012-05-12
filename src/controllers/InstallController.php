<?php
namespace Blocks;

/**
 *
 */
class InstallController extends Controller
{
	/**
	 * Init
	 * @throws HttpException
	 */
	public function init()
	{
		// Return a 404 if Blocks is already installed
		if (!b()->config->devMode && b()->getIsInstalled())
			throw new HttpException(404);
	}

	/**
	 * Index action
	 */
	public function actionIndex()
	{
		// Run the requirements checker
		$reqCheck = new RequirementsChecker();
		$reqCheck->run();
		$vars['reqcheck'] = $reqCheck;

		// Guess the site name based on the host name
		$host = $_SERVER['HTTP_HOST'];
		$hostWords = preg_split('/[\-_\.]+/', $host);
		array_pop($hostWords);
		$vars['sitename'] = implode(' ', array_map('ucfirst', $hostWords));
		$vars['url'] = 'http://'.$host;

		$this->loadTemplate('_special/install', $vars);
	}

	/**
	 * Validates the license key
	 */
	public function actionValidateLicensekey()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$licenseKey = new InstallLicenseKeyForm;
		$licenseKey->licensekey = b()->request->getPost('licensekey');

		if ($licenseKey->validate())
			$return['validates'] = true;
		else
			$return['errors'] = $licenseKey->getErrors();

		$this->returnJson($return);
	}

	/**
	 * Validates the user account credentials
	 */
	public function actionValidateAccount()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$user = new InstallUserForm;
		$user->username = b()->request->getPost('username');
		$user->email = b()->request->getPost('email');
		$user->password = b()->request->getPost('password');

		if ($user->validate())
			$return['validates'] = true;
		else
			$return['errors'] = $user->getErrors();

		$this->returnJson($return);
	}

	/**
	 * Validates the site settings
	 */
	public function actionValidateSite()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$site = new InstallSiteForm;
		$site->sitename = b()->request->getPost('sitename');
		$site->url = b()->request->getPost('url');
		$site->language = b()->request->getPost('language');

		if ($site->validate())
			$return['validates'] = true;
		else
			$return['errors'] = $site->getErrors();

		$this->returnJson($return);
	}

	/**
	 * Install action
	 */
	public function actionInstall()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		// Run the installer
		$inputs['licensekey'] = b()->request->getPost('licensekey');
		$inputs['username']   = b()->request->getPost('username');
		$inputs['email']      = b()->request->getPost('email');
		$inputs['password']   = b()->request->getPost('password');
		$inputs['sitename']   = b()->request->getPost('sitename');
		$inputs['url']        = b()->request->getPost('url');
		$inputs['language']   = b()->request->getPost('language');

		b()->installer->run($inputs);

		$return = array('success' => true);
		$this->returnJson($return);
	}
}
