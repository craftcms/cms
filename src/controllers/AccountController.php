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
	public function actionForgot()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$forgotPasswordForm = new ForgotPasswordForm();
		$forgotPasswordForm->username = b()->request->getPost('username');

		if ($forgotPasswordForm->validate())
		{
			$user = b()->users->getUserByUsernameOrEmail($forgotPasswordForm->username);
			if ($user)
			{
				if (b()->email->sendForgotPasswordEmail($user, b()->sites->getCurrent()))
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

					if (!b()->user->getIsLoggedIn())
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
		$user = b()->users->getCurrent();
		if (b()->security->checkPassword($password, $user->password, $user->enc_type))
			return true;

		return false;
	}
}

