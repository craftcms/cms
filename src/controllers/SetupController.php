<?php
namespace Blocks;

/**
 * Setup Controller
 */
class SetupController extends Controller
{
	/**
	 * Init
	 */
	public function init()
	{
		// Return a 404 if Blocks is already setup
		if (!b()->config->devMode && b()->isSetup)
			throw new HttpException(404);
	}

	/**
	 * License Key form
	 */
	public function actionIndex()
	{
		// Is this a post request?
		if (b()->request->isPostRequest)
		{
			$postLicenseKeyId = b()->request->getPost('licensekey_id');

			if ($postLicenseKeyId)
				$licenseKey = LicenseKey::model()->findById($postLicenseKeyId);

			if (empty($licenseKey))
				$licenseKey = new LicenseKey;

			$licenseKey->license_key = b()->request->getPost('licensekey');

			if ($licenseKey->save())
				$this->redirect('setup/site');
		}
		else
			// Does a license key already exist?
			$licenseKey = LicenseKey::model()->find();

		$this->loadTemplate('_special/setup', array(
			'licenseKey' => $licenseKey
		));
	}

	/**
	 * Site form
	 */
	public function actionSite()
	{
		// Is this a post request?
		if (b()->request->isPostRequest)
		{
			$postSiteId = b()->request->getPost('site_id');

			if ($postSiteId)
				$site = Site::model()->findById($postSiteId);

			if (empty($site))
				$site = new Site;

			$site->name = b()->request->getPost('name');
			$site->handle = b()->request->getPost('handle');
			$site->url = b()->request->getPost('url');
			$site->primary = true;

			if ($site->save())
			{
				if (b()->request->getQuery('goback') === null)
					$this->redirect('setup/account');
				else
					$this->redirect('setup');
			}
		}
		else
			// Does a site already exist?
			$site = Site::model()->find();

		$this->loadTemplate('_special/setup/site', array(
			'site' => $site
		));
	}

	/**
	 * Account form
	 */
	public function actionAccount()
	{
		$passwordInfo = new VerifyPasswordForm();

		// Is this a post request?
		if (b()->request->isPostRequest)
		{
			$postUserId = b()->request->getPost('user_id');

			if ($postUserId)
				$user = User::model()->findById($postUserId);

			if (empty($user))
				$user = new User;

			$user->username = b()->request->getPost('username');
			$user->email = b()->request->getPost('email');
			$user->first_name = b()->request->getPost('first_name');
			$user->last_name = b()->request->getPost('last_name');
			$user->admin = true;

			$password = b()->request->getPost('password');
			$confirmPassword = b()->request->getPost('password_confirm');

			$passwordInfo->password = $password;
			$passwordInfo->confirmPassword = $confirmPassword;

			if ($user->validate())
			{
				if ($passwordInfo->validate())
				{
					$hashAndType = b()->security->hashPassword($password);
					$user->password = $hashAndType['hash'];
					$user->enc_type = $hashAndType['encType'];

					if (($user = b()->users->registerUser($user, $password, false)) !== null)
					{
						$user->status = UserAccountStatus::Active;
						$user->activationcode = null;
						$user->activationcode_issued_date = null;
						$user->activationcode_expire_date = null;
						$user->save(false);

						// Give them the default dashboard widgets
						b()->dashboard->assignDefaultUserWidgets($user->id);

						$loginInfo = new LoginForm();
						$loginInfo->loginName = $user->username;
						$loginInfo->password = $password;

						// log the user in
						$loginInfo->login();

						// setup the default email settings.
						$settings['protocol'] = EmailerType::PhpMail;
						$settings['emailAddress'] = $user->email;
						$settings['senderName'] = b()->sites->primarySite->name;
						b()->email->saveEmailSettings($settings);

						$this->redirect('dashboard');
					}
				}
			}
			else
			{
				// user validation failed, let's check password validation so we can display all the errors at once.
				$passwordInfo->validate();
			}
		}
		else
			// Does an admin user already exist?
			$user = User::model()->find('admin=:admin', array(':admin' => true));

		$this->loadTemplate('_special/setup/account', array(
			'user' => $user,
			'passwordInfo' => $passwordInfo
		));
	}
}
