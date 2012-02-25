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
		$changePasswordInfo->currentPassword = Blocks::app()->request->getPost('current-password');

		if ($changePasswordInfo->validate())
		{
			$user = Blocks::app()->users->current;

			// check their existing password
			$checkPassword = Blocks::app()->security->checkPassword($changePasswordInfo->currentPassword, $user->password, $user->enc_type);

			// bad password
			if (!$checkPassword)
				$changePasswordInfo->addError('currentPassword', 'The password you entered does not match the one we have on record.');
			else
			{
				if (($user = Blocks::app()->users->changePassword($user, $changePasswordInfo->password)) !== false)
				{
					$user->last_password_change_date = DateTimeHelper::currentTime();
					$user->password_reset_required = false;
					$user->save();

					Blocks::app()->user->setMessage(MessageStatus::Success, 'Password successfully changed.');
					$this->redirect('dashboard');
				}
			}
		}

		// display the password form
		$this->loadTemplate(Blocks::app()->users->changePasswordUrl, array('changePasswordInfo' => $changePasswordInfo));
	}

	/**
	 *
	 */
	public function actionForgot()
	{
		$this->requirePostRequest();

		$forgotPasswordInfo = new ForgotPasswordForm();
		$forgotPasswordInfo->loginName = Blocks::app()->request->getPost('loginName');

		if ($forgotPasswordInfo->validate())
		{
			$user = Blocks::app()->users->getByLoginName($forgotPasswordInfo->loginName);

			if ($user == null)
				$forgotPasswordInfo->addError('loginName', 'Invalid username or email.');
			else
			{
				if (Blocks::app()->users->forgotPassword($user))
					$this->redirect(Blocks::app()->users->forgotPasswordUrl.'?success=1');
			}
		}

		$this->loadTemplate(Blocks::app()->users->forgotPasswordUrl, array('forgotPassword' => $forgotPasswordInfo));
	}

	/**
	 * @throws Exception
	 */
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
					$userToChange->activationcode = null;
					$userToChange->activationcode_issued_date = null;
					$userToChange->activationcode_expire_date = null;
					$userToChange->status = UserAccountStatus::Active;
					$userToChange->last_password_change_date = DateTimeHelper::currentTime();
					$userToChange->password_reset_required = false;
					$userToChange->save();

					if (!Blocks::app()->user->isLoggedIn)
						Blocks::app()->user->startLogin($userToChange->username, $verifyAccountForm->password);

					Blocks::app()->dashboard->assignDefaultUserWidgets($userToChange->id);

					Blocks::app()->user->setMessage(MessageStatus::Success, 'Password successfully changed.');
					$this->redirect('dashboard');
				}
			}

			throw new Exception('There was a problem validating this activation code.');
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
}

