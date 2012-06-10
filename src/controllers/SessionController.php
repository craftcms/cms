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

		$username = blx()->request->getPost('username');
		$password = blx()->request->getPost('password');
		$rememberMe = (blx()->request->getPost('rememberMe') === 'y');

		// Attempt to log in
		$loginInfo = blx()->user->startLogin($username, $password, $rememberMe);

		// Did it work?
		if (blx()->user->getIsLoggedIn())
		{
			$r = array(
				'success' => true,
				'redirectUrl' => blx()->user->getReturnUrl()
			);
		}
		else
		{
			// they are not logged in, but they need to reset their password.
			if ($loginInfo->getIdentity()->errorCode === UserIdentity::ERROR_PASSWORD_RESET_REQUIRED)
			{
				$r = array('error' => 'You need to reset your password. Check your email for instructions.');
			}
			else
			{
				// error logging in.
				$errorMessage = '';

				if ($loginInfo->getIdentity()->errorCode === UserIdentity::ERROR_ACCOUNT_LOCKED)
					$errorMessage = 'Account locked.';
				else if ($loginInfo->getIdentity()->errorCode === UserIdentity::ERROR_ACCOUNT_COOLDOWN)
				{
					$user = blx()->users->getUserByUsernameOrEmail($username);
					$errorMessage = 'Account locked. Try again in '.DateTimeHelper::secondsToHumanTimeDuration(blx()->users->getRemainingCooldownTime($user), false).'.';
				}
				else if ($loginInfo->getIdentity()->errorCode === UserIdentity::ERROR_USERNAME_INVALID || $loginInfo->getIdentity()->errorCode === UserIdentity::ERROR_ACCOUNT_SUSPENDED)
					$errorMessage = 'Invalid login name or password.';
				else if ($loginInfo->getIdentity()->errorCode !== UserIdentity::ERROR_NONE)
					$errorMessage = $loginInfo->getIdentity()->failedPasswordAttemptCount.' of '.blx()->config->maxInvalidPasswordAttempts.' failed password attempts.';

				$r = array(
					'error' => $errorMessage,
				);
			}
		}

		$this->returnJson($r);
	}

	/**
	 *
	 */
	public function actionLogout()
	{
		blx()->user->logout();
		$this->redirect('');
	}
}
