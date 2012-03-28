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

			$site->name     = b()->request->getPost('name');
			$site->handle   = b()->request->getPost('handle');
			$site->url      = b()->request->getPost('url');
			$site->language = b()->request->getPost('language');
			$site->primary  = true;

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

			$isNewUser = $user->isNewRecord;

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

						// Log the user in
						$loginInfo = new LoginForm();
						$loginInfo->loginName = $user->username;
						$loginInfo->password = $password;
						$loginInfo->login();

						// The only way they wouldn't be a new user is if devMode is on
						// and they're going through the Setup Wizard a second time
						if ($isNewUser)
							$this->completeSetup($user);
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

	/**
	 * Completes the Setup process
	 * @param User $user The first user
	 * @access private
	 */
	private function completeSetup($user)
	{
		// Save the default email settings
		b()->email->saveEmailSettings(array(
			'protocol' => EmailerType::PhpMail,
			'emailAddress' => $user->email,
			'senderName' => b()->sites->primarySite->name
		));

		// Give them the default dashboard widgets
		b()->dashboard->assignDefaultUserWidgets($user->id);

		// Create a Blog section
		$section = b()->content->saveSection(array(
			'name'       => 'Blog',
			'handle'     => 'blog',
			'url_format' => 'blog/{slug}',
			'template'   => 'blog/_entry',
			'blocks'     => array(
				'new1' => array(
					'class'    => 'PlainText',
					'name'     => 'Summary',
					'handle'   => 'summary',
					'settings' => array(
						'hint'          => 'Enter a summary…',
						'maxLength'     => 100,
						'maxLengthUnit' => 'words'
					)
				),
				'new2' => array(
					'class'    => 'PlainText',
					'name'     => 'Body',
					'handle'   => 'body',
					'required' => true,
					'settings' => array(
						'hint' => 'Enter the body copy…'
					)
				)
			)
		));

		// Add a Welcome entry to the Blog
		$entry = b()->content->createEntry($section->id, null, $user->id, 'Welcome to Blocks Alpha 1');
		b()->content->saveEntrySlug($entry, 'welcome');
		$draft = b()->content->createDraft($entry->id);
		b()->content->saveDraftContent($draft->id, array(
			'summary' => 'It’s here.',
			'body'    => "Hey {$user->first_name},\n\n" .
			             "It’s been over a year since we started Blocks, and it’s finally starting to look like a CMS!\n\n" .
			             "You will find that it’s missing a lot of key features (like the ability to delete this entry). But the groundwork has been laid, so progress comes much quicker now. And the one-click updater is in place, so keeping Blocks up-to-date will be quick and painless.\n\n" .
			             "We couldn’t be more thrilled to be handing out Alpha 1 to our closest friends in the business. We hope you like it, but please don’t hold back any criticism. We only have one chance to make this right.\n\n" .
			             "Thanks for participating!\n" .
			             '-Brandon & Brad'
		));
		b()->content->publishDraft($draft->id);

		// Redirect to the Welcome entry
		$this->redirect('content/edit/'.$entry->id);
	}
}
