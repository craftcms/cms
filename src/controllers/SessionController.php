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
			$r = array(
				'success' => true,
				'redirectUrl' => Blocks::app()->user->returnUrl
			);
		else
		{
			$errorMessage = '';

			if ($loginInfo->identity->errorCode === UserIdentity::ERROR_ACCOUNT_LOCKED)
				$errorMessage = 'This account has been locked.';
			elseif ($loginInfo->identity->errorCode === UserIdentity::ERROR_ACCOUNT_COOLDOWN)
				$errorMessage = 'Cooldown man.  '.DateTimeHelper::niceSeconds($loginInfo->identity->cooldownTimeRemaining).' remaining.';
			elseif ($loginInfo->identity->errorCode !== UserIdentity:: ERROR_NONE)
				$errorMessage = 'Invalid login name or password. '.$loginInfo->identity->failedPasswordAttemptCount.'/'.Blocks::app()->config->getItem('maxInvalidPasswordAttempts').' attempts.';

			$r = array(
				'error' => $errorMessage,
			);
		}

		$this->returnJson($r);
	}

	public function actionLogout()
	{
		Blocks::app()->user->logout();
		$this->redirect('');
	}
}
