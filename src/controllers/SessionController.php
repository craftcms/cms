<?php
namespace Blocks;

/**
 * Handles session related tasks including logging in and out.
 */
class SessionController extends BaseController
{
	/**
	 * Displays the login template. If valid login information, redirects to previous template.
	 */
	public function actionLogin()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$loginName = Blocks::app()->request->getPost('loginName');
		$password = Blocks::app()->request->getPost('password');
		$rememberMe = (Blocks::app()->request->getPost('rememberMe') === 'y');

		// Attempt to log in
		$loginInfo = Blocks::app()->user->startLogin($loginName, $password, $rememberMe);

		// Did it work?
		if (Blocks::app()->user->isLoggedIn)
		{
			$r = array(
				'success' => true,
				'redirectUrl' => Blocks::app()->user->returnUrl
			);
		}
		else
		{
			// they are not logged in, but they need to reset their password.
			if ($loginInfo->identity->errorCode === UserIdentity::ERROR_PASSWORD_RESET_REQUIRED)
			{
				$r = array(
					'success' => true,
					'redirectUrl' => Blocks::app()->users->forgotPasswordUrl.'?success=1'
				);
			}
			else
			{
				// error logging in.
				$errorMessage = '';

				if ($loginInfo->identity->errorCode === UserIdentity::ERROR_ACCOUNT_LOCKED)
					$errorMessage = 'Account locked.';
				else if ($loginInfo->identity->errorCode === UserIdentity::ERROR_ACCOUNT_COOLDOWN)
					$errorMessage = 'Account locked. Try again in '.DateTimeHelper::secondsToHumanTimeDuration($loginInfo->identity->cooldownTimeRemaining, false).'.';
				else if ($loginInfo->identity->errorCode === UserIdentity::ERROR_USERNAME_INVALID)
					$errorMessage = 'Invalid login name or password.';
				else if ($loginInfo->identity->errorCode !== UserIdentity::ERROR_NONE)
					$errorMessage = $loginInfo->identity->failedPasswordAttemptCount.' of '.Blocks::app()->config->getItem('maxInvalidPasswordAttempts').' failed password attempts.';

				$r = array(
					'error' => $errorMessage,
				);
			}
		}

		$this->returnJson($r);
	}

	public function actionLogout()
	{
		Blocks::app()->user->logout();
		$this->redirect('');
	}
}
