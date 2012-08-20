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
	public function actionForgotPassword()
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
				// Generate a new verification code
				blx()->users->generateVerificationCode($user);

				// Send the Forgot Password email
				$link = UrlHelper::generateUrl(blx()->users->getVerifyAccountUrl(), array('code' => $user->verification_code));
				if (blx()->email->sendEmailByKey($user, 'forgot_password', array('link' => $link)))
					$this->returnJson(array('success' => true));

				$this->returnErrorJson(Blocks::t('There was a problem sending the forgot password email.'));
			}
		}

		$this->returnErrorJson(Blocks::t('Invalid Username or Email.'));
	}

	/**
	 * @throws Exception
	 */
	public function actionVerify()
	{
		$this->requirePostRequest();

		$verificationCode = blx()->request->getRequiredPost('verificationCode');
		$password = blx()->request->getRequiredPost('password');

		$passwordForm = new PasswordForm();
		$passwordForm->password = $password;

		if ($passwordForm->validate())
		{
			$user = blx()->users->getUserByVerificationCode($verificationCode);

			if ($user)
			{
				blx()->users->changePassword($user, $password, false);

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

