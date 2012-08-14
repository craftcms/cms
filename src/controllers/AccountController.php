<?php
namespace Blocks;

/**
 * Handles account related tasks including updating profiles, changing passwords, etc.
 */
class AccountController extends BaseController
{
	/**
	 *
	 */
	public function actionForgot()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$forgotPasswordForm = new ForgotPasswordForm();
		$forgotPasswordForm->username = blx()->request->getPost('username');

		if ($forgotPasswordForm->validate())
		{
			$user = blx()->users->getUserByUsernameOrEmail($forgotPasswordForm->username);
			if ($user)
			{
				if (blx()->email->sendEmail($user, 'forgot_password'))
					$this->returnJson(array('success' => true));

				$this->returnErrorJson('There was a problem sending the forgot password email.');
			}
		}

		$this->returnErrorJson('Invalid username or email.');
	}

	/**
	 * @throws Exception
	 */
	public function actionVerify()
	{
		$this->requirePostRequest();

		$passwordForm = new PasswordForm();
		$passwordForm->password = blx()->request->getPost('password');

		if ($passwordForm->validate())
		{
			$userToChange = blx()->users->getUserById(blx()->request->getPost('userId'));

			if ($userToChange !== null)
			{
				if (($userToChange = blx()->users->changePassword($userToChange, $passwordForm->password)) !== false)
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

					if (!blx()->user->getIsLoggedIn())
						blx()->user->startLogin($userToChange->username, $passwordForm->password);

					blx()->dashboard->assignDefaultUserWidgets($userToChange->id);

					blx()->user->setNotice('Password updated.');
					$this->redirect('dashboard');
				}
			}

			throw new Exception(Blocks::t('There was a problem validating this activation code.'));
		}

		// display the verify account form
		$this->renderTemplate('verify', array('verifyAccountInfo' => $passwordForm));
	}

	/**
	 * @param $password
	 * @return bool
	 */
	public function checkPassword($password)
	{
		$user = blx()->users->getCurrentUser();
		if (blx()->security->checkPassword($password, $user->password, $user->enc_type))
			return true;

		return false;
	}
}

