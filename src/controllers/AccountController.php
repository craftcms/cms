<?php
namespace Blocks;

/**
 * Handles account related tasks including updating profiles, changing passwords, etc.
 */
class AccountController extends BaseController
{
	public function actionChangePassword()
	{
		$this->requirePostRequest();

		$changePasswordInfo = new ChangePasswordForm();

		$changePasswordInfo->confirmPassword = Blocks::app()->request->getPost('confirm-password');
		$changePasswordInfo->password = Blocks::app()->request->getPost('password');

		if ($changePasswordInfo->validate())
		{
			// this is a user that is verifying an auth code
			$userToChange = Blocks::app()->users->getById(Blocks::app()->request->getPost('userId'));

			// user is already logged in meaning it's not a new account auth code request
			if (Blocks::app()->user->isLoggedIn && $userToChange == null)
			{
				// we do manual validation of current password here.
				$currentPassword = Blocks::app()->request->getPost('current-password');
				if (StringHelper::isNullOrEmpty($currentPassword))
					$changePasswordInfo->addError('currentPassword', 'Current password cannot be empty.');
				else
				{
					$user = Blocks::app()->users->current;

					// check their existing password
					$checkPassword = Blocks::app()->security->checkPassword($currentPassword, $user->password, $user->enc_type);

					// bad password
					if (!$checkPassword)
					{
						$changePasswordInfo->addError('currentPassword', 'The password you entered does not match the one we have on record.');
					}
					else
					{
						// everything looks good.
						$this->_processChangePassword($user, $changePasswordInfo->password);
					}
				}
			}
		}

		// display the password form
		$this->loadTemplate(Blocks::app()->users->changePasswordUrl, array('changePasswordInfo' => $changePasswordInfo));
	}

	public function actionVerify()
	{
		$this->requirePostRequest();

		$verifyAccountForm = new VerifyAccountForm();

		$verifyAccountForm->password = Blocks::app()->request->getPost('password');
		$verifyAccountForm->confirmPassword = Blocks::app()->request->getPost('confirm-password');

		if ($verifyAccountForm->validate())
		{
			$userToChange = Blocks::app()->users->getById(Blocks::app()->request->getPost('userId'));

			if ($userToChange !== null)
			{
				if (($userToChange = Blocks::app()->users->changePassword($userToChange, $verifyAccountForm->password)) !== false)
				{
					$userToChange->authcode = null;
					$userToChange->authcode_issued_date = null;
					$userToChange->authcode_expire_date = null;
					$userToChange->status = UserAccountStatus::Active;
					$userToChange->last_password_change_date = DateTimeHelper::currentTime();
					$userToChange->password_reset_required = false;
					$userToChange->save();

					if (!Blocks::app()->user->isLoggedIn)
						Blocks::app()->user->startLogin($userToChange->username, $password)

					Blocks::app()->user->setMessage(MessageStatus::Success, 'Password successfully changed.');
					$this->redirect('dashboard');
				}
			}

			throw new Exception('There was a problem validating this auth code.');
		}

		// display the verify account form
		$this->loadTemplate('verify', array('verifyAccountInfo' => $verifyAccountForm));
	}

	/**
	 * @param $password
	 * @return bool
	 */
	public function checkPassword($password)
	{
		$user = Blocks::app()->users->current;
		if (Blocks::app()->security->checkPassword($password, $user->password, $user->enc_type))
			return true;

		return false;
	}

	/**
	 * @param User $user
	 * @param      $password
	 * @param bool $authCodeRequest
	 */
	private function _processChangePassword(User $user, $password, $authCodeRequest = false)
	{
		if (($user = Blocks::app()->users->changePassword($user, $password)) !== false)
		{
			if ($authCodeRequest)
			{
				$user->authcode = null;
				$user->authcode_issued_date = null;
				$user->authcode_expire_date = null;
				$user->status = UserAccountStatus::Active;
				$user->save();
			}

			$user->last_password_change_date = DateTimeHelper::currentTime();
			$user->password_reset_required = false;
			$user->save();

			if (!Blocks::app()->user->isLoggedIn)
			{
				if (($loginInfo = Blocks::app()->user->startLogin($user->username, $password)))
					$this->redirect(Blocks::app()->user->returnUrl);
			}
			else
			{
				Blocks::app()->user->setMessage(MessageStatus::Success, 'Password successfully changed.');
				$this->redirect('dashboard');
			}

			Blocks::log('Successfully changed password for user: '.$user->username.', but could not log them in.');
			Blocks::app()->user->setMessage(MessageStatus::Error, 'There was a problem logging you in.');
			$this->redirect('dashboard');
		}
	}
}

