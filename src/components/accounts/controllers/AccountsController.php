<?php
namespace Blocks;

/**
 * Handles user account related tasks.
 */
class AccountsController extends BaseController
{
	protected $allowAnonymous = array('actionLogin', 'actionForgotPassword', 'actionResetPassword', 'actionSaveUser');

	/**
	 * Displays the login template. If valid login information, redirects to previous template.
	 */
	public function actionLogin()
	{
		$this->requirePostRequest();

		$loginName = blx()->request->getPost('loginName');
		$password = blx()->request->getPost('password');
		$rememberMe = (bool) blx()->request->getPost('rememberMe');

		if (blx()->user->login($loginName, $password, $rememberMe))
		{
			$redirectUrl = blx()->user->getReturnUrl();

			if (blx()->request->isAjaxRequest())
			{
				$this->returnJson(array(
					'success' => true
				));
			}
			else
			{
				blx()->user->setNotice(Blocks::t('Logged in.'));
				$this->redirectToPostedUrl();
			}
		}
		else
		{
			$errorCode = blx()->user->getLoginErrorCode();

			switch ($errorCode)
			{
				case UserIdentity::ERROR_PASSWORD_RESET_REQUIRED:
				{
					$error = Blocks::t('You need to reset your password. Check your email for instructions.');
					break;
				}
				case UserIdentity::ERROR_ACCOUNT_LOCKED:
				{
					$error = Blocks::t('Account locked.');
					break;
				}
				case UserIdentity::ERROR_ACCOUNT_COOLDOWN:
				{
					$user = blx()->accounts->getUserByUsernameOrEmail($loginName);
					$timeRemaining = $user->getRemainingCooldownTime();

					if ($timeRemaining)
					{
						$humanTimeRemaining = $timeRemaining->humanDuration(false);
						$error = Blocks::t('Account locked. Try again in {time}.', array('time' => $humanTimeRemaining));
					}
					else
					{
						$error = Blocks::t('Account locked.');
					}
					break;
				}
				case UserIdentity::ERROR_ACCOUNT_SUSPENDED:
				{
					$error = Blocks::t('Account suspended.');
					break;
				}
				default:
				{
					$error = Blocks::t('Invalid username or password.');
				}
			}

			if (blx()->request->isAjaxRequest())
			{
				$this->returnJson(array(
					'errorCode' => $errorCode,
					'error' => $error
				));
			}
			else
			{
				blx()->user->setError($error);

				$this->renderRequestedTemplate(array(
					'loginName' => $loginName,
					'rememberMe' => $rememberMe
				));
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

		$user = blx()->accounts->getUserByUsernameOrEmail($loginName);

		if ($user)
		{
			if (blx()->accounts->sendForgotPasswordEmail($user))
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

		$user = blx()->accounts->getUserByVerificationCode($verificationCode);

		if (!$user)
		{
			throw new Exception('Invalid verification code.');
		}

		$user->newPassword = blx()->request->getRequiredPost('newPassword');

		if (blx()->accounts->changePassword($user))
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
				'errors' => $user->getErrors('newPassword')
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

			if ($userId)
			{
				$this->requireLogin();
			}
		}
		else
		{
			$this->requireLogin();
			$userId = blx()->user->getUser()->id;
		}

		if ($userId)
		{
			if ($userId != blx()->user->getUser()->id)
			{
				blx()->user->requirePermission('editUsers');
			}

			$user = blx()->accounts->getUserById($userId);

			if (!$user)
			{
				throw new Exception(Blocks::t('No user exists with the ID “{id}”.', array('id' => $userId)));
			}
		}
		else
		{
			if (!blx()->systemSettings->getSetting('users', 'allowPublicRegistration', false))
			{
				blx()->user->requirePermission('registerUsers');
			}

			$user = new UserModel();
		}

		$user->username = blx()->request->getPost('username');
		$user->email = blx()->request->getPost('email');
		$user->emailFormat = blx()->request->getPost('emailFormat');
		$user->language = blx()->request->getPost('language');

		// Only admins can opt out of requiring email verification
		if (!$user->id)
		{
			if (blx()->user->isAdmin())
			{
				$user->verificationRequired = (bool) blx()->request->getPost('verificationRequired');
			}
			else
			{
				$user->verificationRequired = true;
			}
		}

		// Only admins can change other users' passwords
		if (!$user->id || $user->isCurrent() || blx()->user->isAdmin())
		{
			$user->newPassword = blx()->request->getPost('newPassword');
		}

		// Only admins can require users to reset their passwords
		if (blx()->user->isAdmin())
		{
			$user->passwordResetRequired = (bool)blx()->request->getPost('passwordResetRequired');
		}

		if (blx()->accounts->saveUser($user))
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
				'account' => $user
			));
		}
	}
}
