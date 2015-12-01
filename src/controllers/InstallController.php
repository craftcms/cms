<?php
namespace Craft;

/**
 * The InstallController class is a controller that directs all installation related tasks such as creating the database
 * schema and default content for a Craft installation.
 *
 * Note that all actions in the controller are open to do not require an authenticated Craft session in order to execute.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.controllers
 * @since     1.0
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
	 * login on a few, it's best to use {@link UserSessionService::requireLogin() craft()->userSession->requireLogin()}
	 * in the individual methods.
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
		if (!craft()->config->get('devMode') && craft()->isInstalled())
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
		craft()->runController('templates/requirementscheck');

		// Guess the site name based on the server name
		$server = craft()->request->getServerName();
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
		$username = craft()->request->getPost('username');
		if (!$username)
		{
			$username = craft()->request->getPost('email');
		}

		$accountSettings->username = $username;
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
	 * Validates the site settings.
	 *
	 * @return null
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
	 * Install action.
	 *
	 * @return null
	 */
	public function actionInstall()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		// Run the installer
		$username = craft()->request->getPost('username');

		if (!$username)
		{
			$username = craft()->request->getPost('email');
		}

		$inputs['username']   = $username;
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
