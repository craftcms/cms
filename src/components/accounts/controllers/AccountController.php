<?php
namespace Blocks;

/**
 * Handles user account related tasks.
 */
class AccountController extends BaseController
{
	protected $allowAnonymous = array('actionLogin', 'actionForgotPassword', 'actionResetPassword');

	/**
	 * Displays the login template. If valid login information, redirects to previous template.
	 */
	public function actionLogin()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$loginName = blx()->request->getPost('loginName');
		$password = blx()->request->getPost('password');
		$rememberMe = (bool)blx()->request->getPost('rememberMe');

		if (blx()->user->login($loginName, $password, $rememberMe))
		{
			$this->returnJson(array(
				'success' => true,
				'redirectUrl' => blx()->user->getReturnUrl()
			));
		}
		else
		{
			switch (blx()->user->getLoginErrorCode())
			{
				case UserIdentity::ERROR_PASSWORD_RESET_REQUIRED:
				{
					$this->returnJson(array(
						'notice' => Blocks::t('You need to reset your password. Check your email for instructions.')
					));
					break;
				}
				case UserIdentity::ERROR_ACCOUNT_LOCKED:
				{
					$this->returnErrorJson(Blocks::t('Account locked.'));
					break;
				}
				case UserIdentity::ERROR_ACCOUNT_COOLDOWN:
				{
					$user = blx()->account->getUserByUsernameOrEmail($loginName);
					$timeRemaining = $user->getRemainingCooldownTime();
					if ($timeRemaining)
					{
						$humanTimeRemaining = $timeRemaining->humanDuration(false);
						$this->returnErrorJson(Blocks::t('Account locked. Try again in {time}.', array('time' => $humanTimeRemaining)));
					}
					else
					{
						$this->returnErrorJson(Blocks::t('Account locked.'));
					}
					break;
				}
				case UserIdentity::ERROR_ACCOUNT_SUSPENDED:
				{
					$this->returnErrorJson(Blocks::t('Account suspended.'));
					break;
				}
				default:
				{
					$this->returnErrorJson(Blocks::t('Invalid username or password.').'<br><a>'.Blocks::t('Forget your password?').'</a>');
				}
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

	/**
	 * Sends a Forgot Password email.
	 */
	public function actionForgotPassword()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$loginName = blx()->request->getRequiredPost('loginName');

		$user = blx()->account->getUserByUsernameOrEmail($loginName);
		if ($user)
		{
			if (blx()->account->sendForgotPasswordEmail($user))
			{
				$this->returnJson(array('success' => true));
			}
			else
			{
				$this->returnErrorJson(Blocks::t('There was a problem sending the forgot password email.'));
			}
		}
		else
		{
			$this->returnErrorJson(Blocks::t('Invalid username or email.'));
		}
	}

	/**
	 * Resets a user's password once they've verified they have access to their email.
	 */
	public function actionResetPassword()
	{
		$this->requirePostRequest();

		$verificationCode = blx()->request->getRequiredPost('verificationCode');

		$user = blx()->account->getUserByVerificationCode($verificationCode);
		if (!$user)
		{
			throw new Exception('Invalid verification code.');
		}

		$user->newPassword = blx()->request->getRequiredPost('newPassword');

		if (blx()->account->changePassword($user))
		{
			if (!blx()->user->isLoggedIn())
			{
				blx()->user->login($user->username, $user->newPassword);
			}

			blx()->user->setNotice(Blocks::t('Password updated.'));
			$this->redirect('dashboard');
		}
		else
		{
			$this->renderRequestedTemplate(array(
				'error' => true
			));
		}
	}

	/**
	 * Registers a new user, or saves an existing user's account settings.
	 */
	public function actionSaveUser()
	{
		$this->requirePostRequest();

		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			$userId = blx()->request->getPost('userId');
		}
		else
		{
			$userId = blx()->account->getCurrentUser()->id;
		}

		if ($userId)
		{
			$user = blx()->account->getUserById($userId);
			if (!$user)
			{
				throw new Exception(Blocks::t('No user exists with the ID “{id}”.', array('id' => $userId)));
			}
		}
		else
		{
			$user = new UserModel();
		}

		$user->username = blx()->request->getPost('username');
		$user->email = blx()->request->getPost('email');
		$user->emailFormat = blx()->request->getPost('emailFormat');

		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$user->language = blx()->request->getPost('language');
		}

		// Only admins can opt out of email verification
		if (!$user->id)
		{
			if (blx()->account->isAdmin())
			{
				$user->verificationRequired = (bool)blx()->request->getPost('verificationRequired');
			}
			else
			{
				$user->verificationRequired = true;
			}
		}

		// Only admins can change other users' passwords
		if ($user->isCurrent() || blx()->account->isAdmin())
		{
			$user->newPassword = blx()->request->getPost('newPassword');
		}

		// Only admins can require users to reset their passwords
		if (blx()->account->isAdmin())
		{
			$user->passwordResetRequired = (bool)blx()->request->getPost('passwordResetRequired');
		}

		if ($user->save())
		{
			blx()->user->setNotice(Blocks::t('User saved.'));
			$this->redirectToPostedUrl(array(
				'userId' => $user->id
			));
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save user.'));
			$this->renderRequestedTemplate(array(
				'user' => $user
			));
		}
	}
}
