<?php
namespace Blocks;

/**
 * Handles user account related tasks.
 */
class AccountController extends BaseController
{
	protected $allowAnonymous = array('actionForgotPassword', 'actionResetPassword');

	// -------------------------------------------
	//  Password Reset
	// -------------------------------------------

	/**
	 * Sends a Forgot Password email.
	 */
	public function actionForgotPassword()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$username = new UsernameModel();
		$username->username = blx()->request->getPost('username');

		if ($username->validate())
		{
			$user = blx()->account->getUserByUsernameOrEmail($username->username);

			if ($user)
			{
				// Generate a new verification code
				blx()->account->generateVerificationCode($user);

				// Send the Forgot Password email
				$link = UrlHelper::getUrl(blx()->account->getVerifyAccountUrl(), array('code' => $user->verificationCode));

				if (blx()->email->sendEmailByKey($user, 'forgot_password', array('link' => $link)))
				{
					$this->returnJson(array('success' => true));
				}

				$this->returnErrorJson(Blocks::t('There was a problem sending the forgot password email.'));
			}
		}

		$this->returnErrorJson(Blocks::t('Invalid Username or Email.'));
	}

	/**
	 * Resets a user's password once they've verified they have access to their email.
	 */
	public function actionResetPassword()
	{
		$this->requirePostRequest();

		$verificationCode = blx()->request->getRequiredPost('verificationCode');
		$password = blx()->request->getRequiredPost('password');

		$passwordModel = new PasswordModel();
		$passwordModel->password = $password;

		if ($passwordModel->validate())
		{
			$user = blx()->account->getUserByVerificationCode($verificationCode);

			if ($user)
			{
				blx()->account->changePassword($user, $password, false);

				$user->verificationCode = null;
				$user->verificationCodeIssuedDate = null;
				$user->verificationCodeExpiryDate = null;
				$user->status = UserAccountStatus::Active;
				$user->lastPasswordChangeDate = DateTimeHelper::currentTime();
				$user->passwordResetRequired = false;
				$user->failedPasswordAttemptCount = null;
				$user->failedPasswordAttemptWindowStart = null;
				$user->cooldownStart = null;
				$user->save();

				if (!blx()->user->isLoggedIn())
				{
					blx()->user->startLogin($user->username, $passwordModel->password);
				}

				blx()->user->setNotice(Blocks::t('Password updated.'));
				$this->redirect('dashboard');
			}
			else
			{
				throw new Exception(Blocks::t('There was a problem validating this verification code.'));
			}
		}

		// display the verify account form
		$this->renderTemplate('verify', array('verifyAccountInfo' => $passwordModel));
	}

	/**
	 * Registers a new user, or saves an existing user's account settings.
	 */
	public function actionSaveUser()
	{
		$this->requirePostRequest();

		if (Blocks::hasPackage(PackageType::Users))
		{
			$userId = blx()->request->getPost('userId');
			if ($userId !== null)
			{
				$user = blx()->users->getUserById($userId);

				if (!$user)
				{
					throw new Exception(Blocks::t('No user exists with the ID “{id}”.', array('id' => $userId)));
				}
			}
			else
			{
				$user = new UserRecord();
			}
		}
		else
		{
			$user = blx()->account->getCurrentUser();
		}

		$isNewUser = $user->isNewRecord();

		// Set all the standard stuff
		$user->username = blx()->request->getPost('username');
		$user->email = blx()->request->getPost('email');
		$user->emailFormat = blx()->request->getPost('emailFormat');

		if (Blocks::hasPackage(PackageType::Language))
		{
			$user->language = blx()->request->getPost('language');
		}
		else
		{
			$user->language = blx()->language;
		}

		// New password?
		//  - Only admins can change other members' passwords, and even then, they're encouraged to require a password reset.
		if ($user->isCurrent() || blx()->account->getCurrentUser()->admin)
		{
			$password = blx()->request->getPost('password');

			if ($password)
			{
				// Make sure the passwords match and are at least the minimum length
				$passwordModel = new PasswordModel();
				$passwordModel->password = $password;

				$passwordValidates = $passwordModel->validate();

				if ($passwordValidates)
				{
					// Store the new hashed password on the User record, but don't save it yet
					blx()->account->changePassword($user, $password, false);
				}
			}
		}

		// Require a password reset?
		//  - Only admins are allowed to set this
		if (blx()->account->getCurrentUser()->admin)
		{
			$user->passwordResetRequired = (bool)blx()->request->getPost('requirePasswordReset');
		}

		// Run user validation
		$userValidates = $user->validate();

		if ($userValidates && (!isset($passwordValidates) || $passwordValidates))
		{
			// Send a verification email?
			//  - Only an option when registering a new user
			//  - Only admins have a choice in the matter. Verification emails _must_ be sent when a non-admin registers a user.
			if ($isNewUser && (!blx()->account->getCurrentUser()->admin || blx()->request->getPost('requireVerification')))
			{
				$user->status = UserAccountStatus::Pending;
				blx()->account->generateVerificationCode($user, false);
				blx()->email->sendEmailByKey($user, 'verify_email');
			}

			$user->save();

			if ($isNewUser)
			{
				blx()->user->setNotice(Blocks::t('User registered.'));
			}
			else
			{
				blx()->user->setNotice(Blocks::t('Account settings saved.'));
			}

			$this->redirectToPostedUrl();
		}
		else
		{
			if ($isNewUser)
			{
				blx()->user->setError(Blocks::t('Couldn’t register user.'));
			}
			else
			{
				blx()->user->setError(Blocks::t('Couldn’t save account settings.'));
			}

			$this->renderRequestedTemplate(array(
				'user' => $user,
				'passwordForm' => (isset($passwordModel) ? $passwordModel : null)
			));
		}
	}
}
