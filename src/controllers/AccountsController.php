<?php
namespace Blocks;

/**
 * Handles account related tasks including updating profiles, changing passwords, etc.
 */
class AccountsController extends BaseController
{
	// Forgot / Reset Password

	/**
	 * Sends a Forgot Password email.
	 */
	public function actionForgotPassword()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$forgotPasswordForm = new ForgotPasswordForm();
		$forgotPasswordForm->username = blx()->request->getPost('username');

		if ($forgotPasswordForm->validate())
		{
			$user = blx()->accounts->getUserByUsernameOrEmail($forgotPasswordForm->username);
			if ($user)
			{
				// Generate a new verification code
				blx()->accounts->generateVerificationCode($user);

				// Send the Forgot Password email
				$link = UrlHelper::generateUrl(blx()->accounts->getVerifyAccountUrl(), array('code' => $user->verification_code));
				if (blx()->email->sendEmailByKey($user, 'forgot_password', array('link' => $link)))
					$this->returnJson(array('success' => true));

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

		$passwordForm = new PasswordForm();
		$passwordForm->password = $password;

		if ($passwordForm->validate())
		{
			$user = blx()->accounts->getUserByVerificationCode($verificationCode);

			if ($user)
			{
				blx()->accounts->changePassword($user, $password, false);

				$user->verification_code = null;
				$user->verification_code_issued_date = null;
				$user->verification_code_expiry_date = null;
				$user->status = UserAccountStatus::Active;
				$user->last_password_change_date = DateTimeHelper::currentTime();
				$user->password_reset_required = false;
				$user->failed_password_attempt_count = null;
				$user->failed_password_attempt_window_start = null;
				$user->cooldown_start = null;
				$user->save();

				if (!blx()->user->getIsLoggedIn())
					blx()->user->startLogin($user->username, $passwordForm->password);

				blx()->user->setNotice(Blocks::t('Password updated.'));
				$this->redirect('dashboard');
			}
			else
			{
				throw new Exception(Blocks::t('There was a problem validating this verification code.'));
			}
		}

		// display the verify account form
		$this->renderTemplate('verify', array('verifyAccountInfo' => $passwordForm));
	}

	// Account / Profile / Admin Settings

	/**
	 * Registers a new user, or saves an existing user's account settings.
	 */
	public function actionSaveAccountSettings()
	{
		$this->requirePostRequest();

		$userId = blx()->request->getPost('user_id');
		if ($userId !== null)
		{
			$user = blx()->accounts->getUserById($userId);
			if (!$user)
				throw new Exception(Blocks::t('No user exists with the ID “{userId}”.', array('userId' => $userId)));
		}
		else
			$user = new User();

		$isNewUser = $user->getIsNewRecord();

		// Set all the standard stuff
		$user->username = blx()->request->getPost('username');
		$user->email = blx()->request->getPost('email');
		$user->email_format = blx()->request->getPost('email_format');
		$user->preferred_language = blx()->request->getPost('preferred_language');

		// New password?
		//  - Only admins can change other members' passwords, and even then, they're encouraged to require a password reset.
		if ($user->getIsCurrent() || blx()->accounts->getCurrentUser()->admin)
		{
			$password = blx()->request->getPost('password');
			if ($password)
			{
				// Make sure the passwords match and are at least the minimum length
				$passwordForm = new PasswordForm();
				$passwordForm->password = $password;

				$passwordValidates = $passwordForm->validate();
				if ($passwordValidates)
				{
					// Store the new hashed password on the User record, but don't save it yet
					blx()->accounts->changePassword($user, $password, false);
				}
			}
		}

		// Require a password reset?
		//  - Only set this if it's actually in the post
		$passwordResetRequired = blx()->request->getPost('require_password_reset');
		if ($passwordResetRequired !== null)
			$user->password_reset_required = ($passwordResetRequired === 'y');

		// Run user validation
		$userValidates = $user->validate();

		if ($userValidates && (!isset($passwordValidates) || $passwordValidates))
		{
			// Send a verification email?
			//  - Only an option when registering a new user
			//  - Only admins have a choice in the matter. Verification emails _must_ be sent when a non-admin registers a user.
			if ($isNewUser && (!blx()->accounts->getCurrentUser()->admin || blx()->request->getPost('require_verification')))
			{
				$user->status = UserAccountStatus::Pending;
				blx()->accounts->generateVerificationCode($user, false);
				blx()->email->sendEmailByKey($user, 'verify_email');
			}

			$user->save();

			if ($isNewUser)
				blx()->user->setNotice(Blocks::t('User registered.'));
			else
				blx()->user->setNotice(Blocks::t('Account settings saved.'));

			$this->redirectToPostedUrl();
		}
		else
		{
			if ($isNewUser)
				blx()->user->setError(Blocks::t('Couldn’t register user.'));
			else
				blx()->user->setError(Blocks::t('Couldn’t save account settings.'));

			$this->renderRequestedTemplate(array(
				'user' => $user,
				'passwordForm' => (isset($passwordForm) ? $passwordForm : null)
			));
		}
	}

	/**
	 * Saves a user's profile.
	 */
	public function actionSaveProfile()
	{
		$this->requirePostRequest();

		$userId = blx()->request->getRequiredPost('user_id');
		$user = blx()->accounts->getUserById($userId);
		if (!$user)
			throw new Exception(Blocks::t('No user exists with the ID “{userId}”.', array('userId' => $userId)));

		$user->first_name = blx()->request->getPost('first_name');
		$user->last_name = blx()->request->getPost('last_name');

		if ($user->save())
		{
			blx()->user->setNotice(blocks::t('Profile saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save profile.'));
			$this->renderRequestedTemplate(array('user' => $user));
		}
	}

	/**
	 * Saves a user's admin settings.
	 */
	public function actionSaveAdminSettings()
	{
		$this->requirePostRequest();
		$this->requireAdmin();

		$userId = blx()->request->getRequiredPost('user_id');
		$user = blx()->accounts->getUserById($userId);
		if (!$user)
			throw new Exception(Blocks::t('No user exists with the ID “{userId}”.', array('userId' => $userId)));

		$user->admin = (blx()->request->getPost('admin') === 'y');
		$user->save();

		blx()->user->setNotice(Blocks::t('Admin settings saved.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Sends a new verification email to a user.
	 */
	public function actionSendVerificationEmail()
	{
		$this->requirePostRequest();
		$this->requireAdmin();

		$userId = blx()->request->getRequiredPost('user_id');
		$user = blx()->accounts->getUserById($userId);
		if (!$user)
			throw new Exception(Blocks::t('No user exists with the ID “{userId}”.', array('userId' => $userId)));

		$user->status = UserAccountStatus::Pending;
		blx()->accounts->generateVerificationCode($user, false);
		blx()->email->sendEmailByKey($user, 'verify_email');
		$user->save();

		blx()->user->setNotice(Blocks::t('Verification email sent.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Activates a user, bypassing email verification.
	 */
	public function actionActivateUser()
	{
		$this->requirePostRequest();
		$this->requireAdmin();

		$userId = blx()->request->getRequiredPost('user_id');
		$user = blx()->accounts->getUserById($userId);
		if (!$user)
			throw new Exception(Blocks::t('No user exists with the ID “{userId}”.', array('userId' => $userId)));

		blx()->accounts->activateUser($user);

		blx()->user->setNotice(Blocks::t('User activated.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Unlocks a user, bypassing the cooldown phase.
	 */
	public function actionUnlockUser()
	{
		$this->requirePostRequest();
		$this->requireAdmin();

		$userId = blx()->request->getRequiredPost('user_id');
		$user = blx()->accounts->getUserById($userId);
		if (!$user)
			throw new Exception(Blocks::t('No user exists with the ID “{userId}”.', array('userId' => $userId)));

		blx()->accounts->unlockUser($user);

		blx()->user->setNotice(Blocks::t('User activated.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Suspends a user.
	 */
	public function actionSuspendUser()
	{
		$this->requirePostRequest();
		$this->requireAdmin();

		$userId = blx()->request->getRequiredPost('user_id');
		$user = blx()->accounts->getUserById($userId);
		if (!$user)
			throw new Exception(Blocks::t('No user exists with the ID “{userId}”.', array('userId' => $userId)));

		blx()->accounts->suspendUser($user);

		blx()->user->setNotice(Blocks::t('User suspended.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Unsuspends a user.
	 */
	public function actionUnsuspendUser()
	{
		$this->requirePostRequest();
		$this->requireAdmin();

		$userId = blx()->request->getRequiredPost('user_id');
		$user = blx()->accounts->getUserById($userId);
		if (!$user)
			throw new Exception(Blocks::t('No user exists with the ID “{userId}”.', array('userId' => $userId)));

		blx()->accounts->unsuspendUser($user);

		blx()->user->setNotice(Blocks::t('User unsuspended.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Deletes a user.
	 */
	public function actionDeleteUser()
	{
		$this->requirePostRequest();
		$this->requireAdmin();

		$userId = blx()->request->getRequiredPost('user_id');
		$user = blx()->accounts->getUserById($userId);
		if (!$user)
			throw new Exception(Blocks::t('No user exists with the ID “{userId}”.', array('userId' => $userId)));

		blx()->accounts->deleteUser($user);

		blx()->user->setNotice(Blocks::t('User deleted.'));
		$this->redirectToPostedUrl();
	}
}
