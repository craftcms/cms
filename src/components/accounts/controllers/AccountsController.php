<?php
namespace Blocks;

/**
 * Handles user account related tasks.
 */
class AccountsController extends BaseController
{
	protected $allowAnonymous = array('actionLogin', 'actionForgotPassword', 'actionVerify', 'actionResetPassword', 'actionSaveUser');

	/**
	 * Displays the login template, and handles login post requests.
	 */
	public function actionLogin()
	{
		if (blx()->user->isLoggedIn())
		{
			$this->redirect('');
		}

		$vars = array();

		if (blx()->request->isPostRequest())
		{
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
					case UserIdentity::ERROR_NO_CP_ACCESS:
					{
						$error = Blocks::t('You cannot access the CP with that account.');
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

					$vars = array(
						'loginName' => $loginName,
						'rememberMe' => $rememberMe
					);
				}
			}
		}

		if (blx()->request->isCpRequest())
		{
			$template = 'login';
		}
		else
		{
			$template = blx()->config->get('loginPath');
		}

		$this->renderTemplate($template, $vars);
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

		$loginName = blx()->request->getRequiredPost('loginName');

		$user = blx()->accounts->getUserByUsernameOrEmail($loginName);

		if ($user)
		{
			if (blx()->accounts->sendForgotPasswordEmail($user))
			{
				if (blx()->request->isAjaxRequest())
				{
					$this->returnJson(array('success' => true));
				}
				else
				{
					blx()->user->setNotice(Blocks::t('Check your email for instructions to reset your password.'));
					$this->redirectToPostedUrl();
				}
			}
			else
			{
				$error = Blocks::t('There was a problem sending the forgot password email.');
			}
		}
		else
		{
			$error = Blocks::t('Invalid username or email.');
		}

		if (blx()->request->isAjaxRequest())
		{
			$this->returnErrorJson($error);
		}
		else
		{
			blx()->user->setError($error);
			$this->renderRequestedTemplate();
		}
	}

	/**
	 * Resets a user's password once they've verified they have access to their email.
	 */
	public function actionResetPassword()
	{
		if (blx()->user->isLoggedIn())
		{
			$this->redirect('');
		}

		if (blx()->request->isPostRequest())
		{
			$this->requirePostRequest();

			$code = blx()->request->getRequiredPost('code');
			$user = blx()->accounts->getUserByVerificationCode($code);

			if (!$user)
			{
				throw new Exception(Blocks::t('Invalid verification code.'));
			}

			$user->newPassword = blx()->request->getRequiredPost('newPassword');

			if (blx()->accounts->changePassword($user))
			{
				// Log them in
				blx()->user->login($user->username, $user->newPassword);

				blx()->user->setNotice(Blocks::t('Password updated.'));
				$this->redirectToPostedUrl();
			}
			else
			{
				blx()->user->setNotice(Blocks::t('Couldn’t update password.'));

				$this->renderRequestedTemplate(array(
					'errors' => $user->getErrors('newPassword')
				));
			}
		}
		else
		{
			$code = blx()->request->getQuery('code');
			$user = blx()->accounts->getUserByVerificationCode($code);

			if (!$user)
			{
				throw new HttpException(404);
			}

			if (blx()->request->isCpRequest())
			{
				$template = 'resetpassword';
			}
			else
			{
				$template = blx()->config->get('resetPasswordPath');
			}

			$this->renderTemplate($template, array(
				'code' => $code
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
				blx()->user->requireLogin();
			}
		}
		else
		{
			blx()->user->requireLogin();
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
