<?php
namespace Blocks;

/**
 * Handles account related tasks including updating profiles, changing passwords, etc.
 */
class AccountController extends Controller
{
	/**
	 *
	 */
	public function actionChangePassword()
	{
		$this->requirePostRequest();

		$changePasswordInfo = new ChangePasswordForm();

		$changePasswordInfo->confirmPassword = b()->request->getPost('confirm-password');
		$changePasswordInfo->password = b()->request->getPost('password');
		$changePasswordInfo->currentPassword = b()->request->getPost('current-password');

		if ($changePasswordInfo->validate())
		{
			$user = b()->users->current;

			// check their existing password
			$checkPassword = b()->security->checkPassword($changePasswordInfo->currentPassword, $user->password, $user->enc_type);

			// bad password
			if (!$checkPassword)
				$changePasswordInfo->addError('currentPassword', 'The password you entered does not match the one we have on record.');
			else
			{
				if (($user = b()->users->changePassword($user, $changePasswordInfo->password)) !== false)
				{
					$user->last_password_change_date = DateTimeHelper::currentTime();
					$user->password_reset_required = false;
					$user->save();

					b()->user->setMessage(MessageType::Notice, 'Password updated.');
					$this->redirect('account');
				}
			}
		}

		// display the password form
		$this->loadTemplate(b()->users->changePasswordUrl, array('changePasswordInfo' => $changePasswordInfo));
	}

	/**
	 *
	 */
	public function actionForgot()
	{
		$this->requirePostRequest();

		$forgotPasswordInfo = new ForgotPasswordForm();
		$forgotPasswordInfo->loginName = b()->request->getPost('loginName');

		if ($forgotPasswordInfo->validate())
		{
			$user = b()->users->getByLoginName($forgotPasswordInfo->loginName);

			if ($user == null)
				$forgotPasswordInfo->addError('loginName', 'Invalid username or email.');
			else
			{
				if (b()->users->forgotPassword($user))
					$this->redirect(b()->users->forgotPasswordUrl.'?success=1');
			}
		}

		$this->loadTemplate(b()->users->forgotPasswordUrl, array('forgotPassword' => $forgotPasswordInfo));
	}

	/**
	 * @throws Exception
	 */
	public function actionVerify()
	{
		$this->requirePostRequest();

		$verifyPasswordForm = new VerifyPasswordForm();

		$verifyPasswordForm->password = b()->request->getPost('password');
		$verifyPasswordForm->confirmPassword = b()->request->getPost('confirm-password');

		if ($verifyPasswordForm->validate())
		{
			$userToChange = b()->users->getById(b()->request->getPost('userId'));

			if ($userToChange !== null)
			{
				if (($userToChange = b()->users->changePassword($userToChange, $verifyPasswordForm->password)) !== false)
				{
					$userToChange->activationcode = null;
					$userToChange->activationcode_issued_date = null;
					$userToChange->activationcode_expire_date = null;
					$userToChange->status = UserAccountStatus::Active;
					$userToChange->last_password_change_date = DateTimeHelper::currentTime();
					$userToChange->password_reset_required = false;
					$userToChange->failed_password_attempt_count = null;
					$userToChange->failed_password_attempt_window_start = null;
					$userToChange->cooldown_start = null;
					$userToChange->save();

					if (!b()->user->isLoggedIn)
						b()->user->startLogin($userToChange->username, $verifyPasswordForm->password);

					b()->dashboard->assignDefaultUserWidgets($userToChange->id);

					b()->user->setMessage(MessageType::Notice, 'Password updated.');
					$this->redirect('dashboard');
				}
			}

			throw new Exception('There was a problem validating this activation code.');
		}

		// display the verify account form
		$this->loadTemplate('verify', array('verifyAccountInfo' => $verifyPasswordForm));
	}

	/**
	 * @param $password
	 * @return bool
	 */
	public function checkPassword($password)
	{
		$user = b()->users->current;
		if (b()->security->checkPassword($password, $user->password, $user->enc_type))
			return true;

		return false;
	}
}

