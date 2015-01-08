<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use craft\app\Craft;
use craft\app\errors\HttpException;
use craft\app\models\AccountSettings as AccountSettingsModel;
use craft\app\models\SiteSettings as SiteSettingsModel;

/**
 * The InstallController class is a controller that directs all installation related tasks such as creating the database
 * schema and default content for a Craft installation.
 *
 * Note that all actions in the controller are open to do not require an authenticated Craft session in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class InstallController extends BaseController
{
	// Properties
	// =========================================================================

	/**
	 * If set to false, you are required to be logged in to execute any of the given controller's actions.
	 *
	 * If set to true, anonymous access is allowed for all of the given controller's actions.
	 *
	 * If the value is an array of action names, then you must be logged in for any action method except for the ones in
	 * the array list.
	 *
	 * If you have a controller that where the majority of action methods will be anonymous, but you only want require
	 * login on a few, it's best to call [[requireLogin()]] in the individual methods.
	 *
	 * @var bool
	 */
	protected $allowAnonymous = true;

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
		// Return a 404 if Craft is already installed
		if (!Craft::$app->config->get('devMode') && Craft::$app->isInstalled())
		{
			throw new HttpException(404);
		}
	}

	/**
	 * Index action.
	 *
	 * @return null
	 */
	public function actionIndex()
	{
		Craft::$app->runController('templates/requirementscheck');

		// Guess the site name based on the server name
		$server = Craft::$app->request->getServerName();
		$words = preg_split('/[\-_\.]+/', $server);
		array_pop($words);
		$vars['defaultSiteName'] = implode(' ', array_map('ucfirst', $words));
		$vars['defaultSiteUrl'] = 'http://'.$server;

		$this->renderTemplate('_special/install', $vars);
	}

	/**
	 * Validates the user account credentials.
	 *
	 * @return null
	 */
	public function actionValidateAccount()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$accountSettings = new AccountSettingsModel();
		$username = Craft::$app->request->getPost('username');
		if (!$username)
		{
			$username = Craft::$app->request->getPost('email');
		}

		$accountSettings->username = $username;
		$accountSettings->email = Craft::$app->request->getPost('email');
		$accountSettings->password = Craft::$app->request->getPost('password');

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
	 * Validates the site settings.
	 *
	 * @return null
	 */
	public function actionValidateSite()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$siteSettings = new SiteSettingsModel();
		$siteSettings->siteName = Craft::$app->request->getPost('siteName');
		$siteSettings->siteUrl = Craft::$app->request->getPost('siteUrl');

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
	 * Install action.
	 *
	 * @return null
	 */
	public function actionInstall()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		// Run the installer
		$username = Craft::$app->request->getPost('username');

		if (!$username)
		{
			$username = Craft::$app->request->getPost('email');
		}

		$inputs['username']   = $username;
		$inputs['email']      = Craft::$app->request->getPost('email');
		$inputs['password']   = Craft::$app->request->getPost('password');
		$inputs['siteName']   = Craft::$app->request->getPost('siteName');
		$inputs['siteUrl']    = Craft::$app->request->getPost('siteUrl');
		$inputs['locale'  ]   = Craft::$app->request->getPost('locale');

		Craft::$app->install->run($inputs);

		$return = ['success' => true];
		$this->returnJson($return);
	}
}
