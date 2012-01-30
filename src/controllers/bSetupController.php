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
		$licenseKey = Blocks::app()->user->getFlash('licenseKey');

		if (!$licenseKey)
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

		if ($licenseKey->save())
		{
			$this->redirect('setup/account');
		}
		else
		{
			Blocks::app()->user->setFlash('licenseKey', $licenseKey);
			$this->redirect('setup');
		}
	}

	/**
	 * Account form
	 */
	public function actionAccount()
	{
		$user = Blocks::app()->user->getFlash('user');

		if (!$user)
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

		$new = $user->isNewRecord;

		if ($user->save())
		{
			if ($new)
			{
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

			if (Blocks::app()->request->getQuery('goback') === null)
				$this->redirect('setup/site');
			else
				$this->redirect('setup');
		}
		else
		{
			Blocks::app()->user->setFlash('user', $user);
			$this->redirect('setup/account');
		}
	}

	/**
	 * Site form
	 */
	public function actionSite()
	{
		$site = Blocks::app()->user->getFlash('site');

		if (!$site)
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

		if ($site->save())
		{
			if (Blocks::app()->request->getQuery('goback') === null)
				$this->redirect('dashboard');
			else
				$this->redirect('setup/account');
		}
		else
		{
			Blocks::app()->user->setFlash('site', $site);
			$this->redirect('setup/site');
		}
	}
}
