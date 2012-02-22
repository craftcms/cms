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
			// user is already logged in meaning it's not a new account auth code request
			if (Blocks::app()->user->isLoggedIn)
			{
				// we do manual validation of current password here.
				$currentPassword = Blocks::app()->request->getPost('current-password');
				if (StringHelper::isNullOrEmpty($currentPassword))
					$changePasswordInfo->addError('currentPassword', 'Current password cannot be empty.');
				else
				{
					$user = Blocks::app()->user->user;

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
			else
			{
				// this is a user that is verifying an auth code
				$user = Blocks::app()->users->getById(Blocks::app()->request->getPost('userId'));
				if ($user !== null)
				{
					$this->_processChangePassword($user, $changePasswordInfo->password, true);
				}
				else
				{
					Blocks::log('Trying to set the password for an auth code request and could not find the associated user.');
					Blocks::app()->user->setMessage(MessageStatus::Error, 'Could not set the password.');
					$this->redirect('/');
				}
			}
		}

		// display the password form
		$this->loadTemplate('account/password', array('changePasswordInfo' => $changePasswordInfo));
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

			$loginInfo = new LoginForm();

			$loginInfo->loginName = $user->username;
			$loginInfo->password = $password;

			// validate user input and redirect to the previous page if valid
			if ($loginInfo->validate() && $loginInfo->login())
			{
				$this->redirect(Blocks::app()->user->returnUrl);
			}

			Blocks::log('Successfully changed password for user: '.$user->username.', but could not log them in.');
			Blocks::app()->user->setMessage(MessageStatus::Error, 'There was a problem logging you in.');
			$this->redirect('/');
		}
	}

	public function actionLogout()
	{
		Blocks::app()->user->logout();
		$this->redirect('/');
	}
}

