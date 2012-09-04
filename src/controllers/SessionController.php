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
		$rememberMe = (bool)blx()->request->getPost('rememberMe');

		// Attempt to log in
		$loginInfo = blx()->user->startLogin($username, $password, $rememberMe);

		// Did it work?
		if (blx()->user->getIsLoggedIn())
		{
			$this->returnJson(array(
				'success' => true,
				'redirectUrl' => blx()->user->getReturnUrl()
			));
		}
		else
		{
			// they are not logged in, but they need to reset their password.
			if ($loginInfo->getIdentity()->errorCode === UserIdentity::ERROR_PASSWORD_RESET_REQUIRED)
			{
				$this->returnJson(array(
					'notice' => Blocks::t('You need to reset your password. Check your email for instructions.')
				));

			}
			else
			{
				switch ($loginInfo->getIdentity()->errorCode)
				{
					case UserIdentity::ERROR_ACCOUNT_LOCKED:
					{
						$error = Blocks::t('Account locked.');
						break;
					}
					case UserIdentity::ERROR_ACCOUNT_COOLDOWN:
					{
						$user = blx()->accounts->getUserByUsernameOrEmail($username);
						$timeRemaining = DateTimeHelper::secondsToHumanTimeDuration(blx()->accounts->getRemainingCooldownTime($user), false);
						$error = Blocks::t('Account locked. Try again in {time}.', array('time' => $timeRemaining));
						break;
					}
					case UserIdentity::ERROR_ACCOUNT_SUSPENDED:
					{
						$error = Blocks::t('Account suspended.');
						break;
					}
					default:
					{
						$error = Blocks::t('Invalid username or password.').'<br><a>'.Blocks::t('Forget your password?').'</a>';
					}
				}

				$this->returnErrorJson($error);
			}
		}
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
