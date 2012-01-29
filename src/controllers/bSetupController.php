<?php

/**
 * Setup Controller
 */
class bSetupController extends bBaseController
{
	/**
	 * Init
	 */
	public function init()
	{
		// Return a 404 if Blocks is already setup
		if (!Blocks::app()->getConfig('devMode') && Blocks::app()->isSetup)
			throw new bHttpException(404);
	}

	/**
	 * License Key form
	 */
	public function actionIndex()
	{
		// Grab the license key if it's already saved
		$licenseKey = bLicenseKey::model()->find();

		$this->loadTemplate('_special/setup', array(
			'licenseKey' => $licenseKey
		));
	}

	/**
	 * Save License Key
	 */
	public function actionSaveLicenseKey()
	{
		$this->requirePostRequest();

		$postLicenseKeyId = Blocks::app()->request->getPost('licensekey_id');

		if ($postLicenseKeyId)
			$licenseKey = bLicenseKey::model()->findByPk($postLicenseKeyId);

		if (empty($licenseKey))
			$licenseKey = new bLicenseKey;

		$licenseKey->key = Blocks::app()->request->getPost('licensekey');

		if ($licenseKey->isNewRecord)
			$licenseKey->save();
		else
			$licenseKey->update();

		$this->redirectAfterSave('setup/account');
	}

	/**
	 * Account form
	 */
	public function actionAccount()
	{
		// Grab the user if it's already saved
		$user = bUser::model()->find('admin=:admin', array(':admin'=>true));

		$this->loadTemplate('_special/setup/account', array(
			'user' => $user
		));
	}

	/**
	 * Save Account
	 */
	public function actionSaveAccount()
	{
		$this->requirePostRequest();

		$postUserId = Blocks::app()->request->getPost('user_id');

		if ($postUserId)
			$user = bUser::model()->findByPk($postUserId);

		if (empty($user))
			$user = new bUser;

		$user->first_name = Blocks::app()->request->getPost('first_name');
		$user->last_name = Blocks::app()->request->getPost('last_name');
		$user->username = Blocks::app()->request->getPost('username');
		$user->email = Blocks::app()->request->getPost('email');

		$password = Blocks::app()->request->getPost('password');
		$password2 = Blocks::app()->request->getPost('password2');
		if ($password)
			$user->password = $password;

		$user->enc_type = 'md5';
		$user->admin = true;

		if ($user->isNewRecord)
		{
			$user->save();

			// Add the default dashboard widgets
			$widgets = array('bUpdatesWidget', 'bRecentActivityWidget', 'bSiteMapWidget', 'bFeedWidget');
			foreach ($widgets as $i => $widgetClass)
			{
				$widget = new bUserWidget;
				$widget->user_id = $user->id;
				$widget->class = $widgetClass;
				$widget->sort_order = ($i+1);
				$widget->save();
			}
		}
		else
			$user->update();

		$this->redirectAfterSave('setup/site', 'setup');
	}

	/**
	 * Site form
	 */
	public function actionSite()
	{
		// Grab the site if it's already saved
		$site = bSite::model()->find('enabled=:enabled', array(':enabled'=>true));

		$this->loadTemplate('_special/setup/site', array(
			'site' => $site
		));
	}

	/**
	 * Save Site
	 */
	public function actionSaveSite()
	{
		$this->requirePostRequest();

		$postSiteId = Blocks::app()->request->getPost('site_id');

		if ($postSiteId)
			$site = bSite::model()->findByPk($postSiteId);

		if (empty($site))
			$site = new bSite;

		$site->name = Blocks::app()->request->getPost('name');
		$site->handle = Blocks::app()->request->getPost('handle');
		$site->url = Blocks::app()->request->getPost('url');
		$site->enabled = true;

		if ($site->isNewRecord)
			$site->save();
		else
			$site->update();

		$this->redirectAfterSave('dashboard', 'setup/account');
	}

	/**
	 * Redirect after save
	 */
	private function redirectAfterSave($nextUri, $prevUri = 'setup')
	{
		if (Blocks::app()->request->getQuery('goback') === null)
			$url = bUrlHelper::generateUrl($nextUri);
		else
			$url = bUrlHelper::generateUrl($prevUri);

		Blocks::app()->request->redirect($url);
	}
}
