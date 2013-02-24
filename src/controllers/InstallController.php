<?php
namespace Craft;

/**
 *
 */
class InstallController extends BaseController
{
	protected $allowAnonymous = true;

	/**
	 * Init
	 *
	 * @throws HttpException
	 */
	public function init()
	{
		// Return a 404 if Craft is already installed
		if (!craft()->config->get('devMode') && craft()->isInstalled())
		{
			throw new HttpException(404);
		}
	}

	/**
	 * Index action
	 */
	public function actionIndex()
	{
		// Run the requirements checker
		$reqCheck = new RequirementsChecker();
		$reqCheck->run();

		if ($reqCheck->getResult() == InstallStatus::Failure)
		{
			$this->renderTemplate('_special/install/cantinstall', array('reqCheck' => $reqCheck));
		}
		else
		{
			// Guess the site name based on the server name
			$server = craft()->request->getServerName();
			$words = preg_split('/[\-_\.]+/', $server);
			array_pop($words);
			$vars['siteName'] = implode(' ', array_map('ucfirst', $words));
			$vars['siteUrl'] = 'http://'.$server;

			$this->renderTemplate('_special/install', $vars);
		}
	}

	/**
	 * Validates the license key
	 */
	public function actionValidateLicensekey()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$licenseKey = new LicenseKeyModel();
		$licenseKey->licensekey = craft()->request->getPost('licensekey');

		if ($licenseKey->validate())
		{
			$return['validates'] = true;
		}
		else
		{
			$return['errors'] = $licenseKey->getErrors();
		}

		$this->returnJson($return);
	}

	/**
	 * Validates the user account credentials
	 */
	public function actionValidateAccount()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$accountSettings = new AccountSettingsModel();
		$accountSettings->username = craft()->request->getPost('username');
		$accountSettings->email = craft()->request->getPost('email');
		$accountSettings->password = craft()->request->getPost('password');

		if ($accountSettings->validate())
		{
			$return['validates'] = true;
		}
		else
		{
			$return['errors'] = $accountSettings->getErrors();
		}

		$this->returnJson($return);
	}

	/**
	 * Validates the site settings
	 */
	public function actionValidateSite()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$siteSettings = new SiteSettingsModel();
		$siteSettings->siteName = craft()->request->getPost('siteName');
		$siteSettings->siteUrl = craft()->request->getPost('siteUrl');

		if ($siteSettings->validate())
		{
			$return['validates'] = true;
		}
		else
		{
			$return['errors'] = $siteSettings->getErrors();
		}

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
		$inputs['licenseKey'] = craft()->request->getPost('licensekey');
		$inputs['username']   = craft()->request->getPost('username');
		$inputs['email']      = craft()->request->getPost('email');
		$inputs['password']   = craft()->request->getPost('password');
		$inputs['siteName']   = craft()->request->getPost('siteName');
		$inputs['siteUrl']    = craft()->request->getPost('siteUrl');
		$inputs['locale'  ]   = craft()->request->getPost('locale');

		craft()->install->run($inputs);

		$return = array('success' => true);
		$this->returnJson($return);
	}
}
