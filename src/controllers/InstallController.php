<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\HttpException;
use craft\app\migrations\Install;
use craft\app\models\AccountSettings as AccountSettingsModel;
use craft\app\models\SiteSettings as SiteSettingsModel;
use craft\app\web\Controller;
use yii\base\Response;

/**
 * The InstallController class is a controller that directs all installation related tasks such as creating the database
 * schema and default content for a Craft installation.
 *
 * Note that all actions in the controller are open to do not require an authenticated Craft session in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class InstallController extends Controller
{
	// Properties
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected $allowAnonymous = true;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 * @throws HttpException if Craft is already installed
	 */
	public function init()
	{
		// Return a 404 if Craft is already installed
		if (!Craft::$app->getConfig()->get('devMode') && Craft::$app->isInstalled())
		{
			throw new HttpException(404);
		}
	}

	/**
	 * Index action.
	 *
	 * @return Response|string The requirements check response if the server doesn’t meet Craft’s requirements, or the rendering result
	 * @throws Exception if it's an Ajax request and the server doesn’t meet Craft’s requirements
	 */
	public function actionIndex()
	{
		if (($response = Craft::$app->runAction('templates/requirements-check')) !== null)
		{
			return $response;
		}

		// Guess the site name based on the server name
		$server = Craft::$app->getRequest()->getServerName();
		$words = preg_split('/[\-_\.]+/', $server);
		array_pop($words);
		$vars['defaultSiteName'] = implode(' ', array_map('ucfirst', $words));
		$vars['defaultSiteUrl'] = 'http://'.$server;

		return $this->renderTemplate('_special/install', $vars);
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
		$username = Craft::$app->getRequest()->getBodyParam('username');
		if (!$username)
		{
			$username = Craft::$app->getRequest()->getBodyParam('email');
		}

		$accountSettings->username = $username;
		$accountSettings->email = Craft::$app->getRequest()->getBodyParam('email');
		$accountSettings->password = Craft::$app->getRequest()->getBodyParam('password');

		if ($accountSettings->validate())
		{
			$return['validates'] = true;
		}
		else
		{
			$return['errors'] = $accountSettings->getErrors();
		}

		return $this->asJson($return);
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
		$siteSettings->siteName = Craft::$app->getRequest()->getBodyParam('siteName');
		$siteSettings->siteUrl = Craft::$app->getRequest()->getBodyParam('siteUrl');

		if ($siteSettings->validate())
		{
			$return['validates'] = true;
		}
		else
		{
			$return['errors'] = $siteSettings->getErrors();
		}

		return $this->asJson($return);
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

		// Run the install migration
		$request = Craft::$app->getRequest();
		$migrator = Craft::$app->getMigrator();

		$migration = new Install([
			'siteName' => $request->getBodyParam('siteName'),
			'siteUrl'  => $request->getBodyParam('siteUrl'),
			'locale'   => $request->getBodyParam('locale'),
			'username' => $request->getBodyParam('username'),
			'password' => $request->getBodyParam('password'),
			'email'    => $request->getBodyParam('email'),
		]);

		if ($migrator->migrateUp($migration) !== false)
		{
			$success = true;

			// Mark all existing migrations as applied
			foreach ($migrator->getNewMigrations() as $name)
			{
				$migrator->addMigrationHistory($name);
			}
		}
		else
		{
			$success = false;
		}

		return $this->asJson(['success' => $success]);
	}
}
