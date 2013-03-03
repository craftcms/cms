<?php
namespace Craft;

/**
 * Handles user account related tasks.
 */
class UsersController extends BaseController
{
	protected $allowAnonymous = array('actionLogin', 'actionForgotPassword', 'actionVerify', 'actionResetPassword', 'actionSaveUser');

	/**
	 * Displays the login template, and handles login post requests.
	 */
	public function actionLogin()
	{
		if (craft()->userSession->isLoggedIn())
		{
			$this->redirect('');
		}

		$vars = array();

		if (craft()->request->isPostRequest())
		{
			$loginName = craft()->request->getPost('loginName');
			$password = craft()->request->getPost('password');
			$rememberMe = (bool) craft()->request->getPost('rememberMe');

			if (craft()->userSession->login($loginName, $password, $rememberMe))
			{
				if (craft()->request->isAjaxRequest())
				{
					$this->returnJson(array(
						'success' => true
					));
				}
				else
				{
					craft()->userSession->setNotice(Craft::t('Logged in.'));
					$this->redirectToPostedUrl();
				}
			}
			else
			{
				$errorCode = craft()->userSession->getLoginErrorCode();

				switch ($errorCode)
				{
					case UserIdentity::ERROR_PASSWORD_RESET_REQUIRED:
					{
						$error = Craft::t('You need to reset your password. Check your email for instructions.');
						break;
					}
					case UserIdentity::ERROR_ACCOUNT_LOCKED:
					{
						$error = Craft::t('Account locked.');
						break;
					}
					case UserIdentity::ERROR_ACCOUNT_COOLDOWN:
					{
						$user = craft()->users->getUserByUsernameOrEmail($loginName);
						$timeRemaining = $user->getRemainingCooldownTime();

						if ($timeRemaining)
						{
							$humanTimeRemaining = $timeRemaining->humanDuration(false);
							$error = Craft::t('Account locked. Try again in {time}.', array('time' => $humanTimeRemaining));
						}
						else
						{
							$error = Craft::t('Account locked.');
						}
						break;
					}
					case UserIdentity::ERROR_ACCOUNT_SUSPENDED:
					{
						$error = Craft::t('Account suspended.');
						break;
					}
					case UserIdentity::ERROR_NO_CP_ACCESS:
					{
						$error = Craft::t('You cannot access the CP with that account.');
						break;
					}
					case UserIdentity::ERROR_NO_CP_OFFLINE_ACCESS:
					{
						$error = Craft::t('You cannot access the CP while the system is offline with that account.');
						break;
					}
					default:
					{
						$error = Craft::t('Invalid username or password.');
					}
				}

				if (craft()->request->isAjaxRequest())
				{
					$this->returnJson(array(
						'errorCode' => $errorCode,
						'error' => $error
					));
				}
				else
				{
					craft()->userSession->setError($error);

					$vars = array(
						'loginName' => $loginName,
						'rememberMe' => $rememberMe
					);
				}
			}
		}

		if (craft()->request->isCpRequest())
		{
			$template = 'login';
		}
		else
		{
			$template = craft()->config->get('loginPath');
		}

		$this->renderTemplate($template, $vars);
	}

	/**
	 *
	 */
	public function actionLogout()
	{
		craft()->userSession->logout();
		$this->redirect('');
	}

	/**
	 * Sends a Forgot Password email.
	 */
	public function actionForgotPassword()
	{
		$this->requirePostRequest();

		$loginName = craft()->request->getRequiredPost('loginName');

		$user = craft()->users->getUserByUsernameOrEmail($loginName);

		if ($user)
		{
			if (craft()->users->sendForgotPasswordEmail($user))
			{
				if (craft()->request->isAjaxRequest())
				{
					$this->returnJson(array('success' => true));
				}
				else
				{
					craft()->userSession->setNotice(Craft::t('Check your email for instructions to reset your password.'));
					$this->redirectToPostedUrl();
				}
			}
			else
			{
				$error = Craft::t('There was a problem sending the forgot password email.');
			}
		}
		else
		{
			$error = Craft::t('Invalid username or email.');
		}

		if (craft()->request->isAjaxRequest())
		{
			$this->returnErrorJson($error);
		}
		else
		{
			craft()->userSession->setError($error);
		}
	}

	/**
	 * Resets a user's password once they've verified they have access to their email.
	 */
	public function actionResetPassword()
	{
		if (craft()->userSession->isLoggedIn())
		{
			$this->redirect('');
		}

		if (craft()->request->isPostRequest())
		{
			$this->requirePostRequest();

			$code = craft()->request->getRequiredPost('code');
			$id = craft()->request->getRequiredPost('id');
			$user = craft()->users->getUserByVerificationCodeAndUid($code, $id);

			if (!$user)
			{
				throw new Exception(Craft::t('Invalid verification code.'));
			}

			$newPassword = craft()->request->getRequiredPost('newPassword');
			$user->newPassword = $newPassword;

			if (craft()->users->changePassword($user))
			{
				// Log them in
				craft()->userSession->login($user->username, $newPassword);

				craft()->userSession->setNotice(Craft::t('Password updated.'));
				$this->redirectToPostedUrl();
			}
			else
			{
				craft()->userSession->setNotice(Craft::t('Couldn’t update password.'));

				// Send the data back to the template
				craft()->urlManager->setRouteVariables(array(
					'errors' => $user->getErrors('newPassword'),
					'code'   => $code,
					'id'     => $id
				));
			}
		}
		else
		{
			$code = craft()->request->getQuery('code');
			$id = craft()->request->getQuery('id');
			$user = craft()->users->getUserByVerificationCodeAndUid($code, $id);

			if (!$user)
			{
				throw new HttpException(404);
			}

			if (craft()->request->isCpRequest())
			{
				$template = 'resetpassword';
			}
			else
			{
				$template = craft()->config->get('resetPasswordPath');
			}

			$this->renderTemplate($template, array(
				'code' => $code,
				'id' => $id
			));
		}
	}

	/**
	 * Registers a new user, or saves an existing user's account settings.
	 */
	public function actionSaveUser()
	{
		$this->requirePostRequest();

		if (Craft::hasPackage(CraftPackage::Users))
		{
			$userId = craft()->request->getPost('userId');

			if ($userId)
			{
				craft()->userSession->requireLogin();
			}
		}
		else
		{
			craft()->userSession->requireLogin();
			$userId = craft()->userSession->getUser()->id;
		}

		if ($userId)
		{
			if ($userId != craft()->userSession->getUser()->id)
			{
				craft()->userSession->requirePermission('editUsers');
			}

			$user = craft()->users->getUserById($userId);

			if (!$user)
			{
				throw new Exception(Craft::t('No user exists with the ID “{id}”.', array('id' => $userId)));
			}
		}
		else
		{
			if (!craft()->systemSettings->getSetting('users', 'allowPublicRegistration', false))
			{
				craft()->userSession->requirePermission('registerUsers');
			}

			$user = new UserModel();
		}

		$user->username        = craft()->request->getPost('username');
		$user->firstName       = craft()->request->getPost('firstName');
		$user->lastName        = craft()->request->getPost('lastName');
		$user->email           = craft()->request->getPost('email');
		$user->emailFormat     = craft()->request->getPost('emailFormat');
		$user->preferredLocale = craft()->request->getPost('preferredLocale');

		// Only admins can opt out of requiring email verification
		if (!$user->id)
		{
			if (craft()->userSession->isAdmin())
			{
				$user->verificationRequired = (bool) craft()->request->getPost('verificationRequired');
			}
			else
			{
				$user->verificationRequired = true;
			}
		}

		// Only admins can change other users' passwords
		if (!$user->id || $user->isCurrent() || craft()->userSession->isAdmin())
		{
			$user->newPassword = craft()->request->getPost('newPassword');
		}

		// Only admins can require users to reset their passwords
		if (craft()->userSession->isAdmin())
		{
			$user->passwordResetRequired = (bool)craft()->request->getPost('passwordResetRequired');
		}

		if (craft()->users->saveUser($user))
		{
			craft()->userSession->setNotice(Craft::t('User saved.'));
			$this->redirectToPostedUrl(array(
				'userId' => $user->id
			));
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save user.'));

			// Send the account back to the template
			craft()->urlManager->setRouteVariables(array(
				'account' => $user
			));
		}
	}

	/**
	 * Saves a user's profile.
	 */
	public function actionSaveProfile()
	{
		$this->requirePostRequest();

		$userId = craft()->request->getRequiredPost('userId');

		if ($userId != craft()->userSession->getUser()->id)
		{
			craft()->userSession->requirePermission('editUsers');
		}

		$user = craft()->users->getUserById($userId);

		if (!$user)
		{
			throw new Exception(Craft::t('No user exists with the ID “{id}”.', array('id' => $userId)));
		}

		$fields = craft()->request->getPost('fields', array());
		$user->setContent($fields);

		if (craft()->users->saveProfile($user))
		{
			craft()->userSession->setNotice(Craft::t('Profile saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save profile.'));
		}

		// Send the account back to the template
		craft()->urlManager->setRouteVariables(array(
			'account' => $user,
		));
	}

	/**
	 * Upload a user photo.
	 */
	public function actionUploadUserPhoto()
	{
		$this->requireAjaxRequest();
		craft()->userSession->requireLogin();
		$userId = craft()->request->getRequiredQuery('userId');

		if ($userId != craft()->userSession->getUser()->id)
		{
			craft()->userSession->requirePermission('editUsers');
		}

		// Upload the file and drop it in the temporary folder
		$uploader = new \qqFileUploader();

		try
		{
			// Make sure a file was uploaded
			if ($uploader->file && $uploader->file->getSize())
			{
				$user = craft()->users->getUserById($userId);

				$folderPath = craft()->path->getTempUploadsPath().'userphotos/'.$user->username.'/';

				IOHelper::clearFolder($folderPath);

				IOHelper::ensureFolderExists($folderPath);
				$fileName = IOHelper::cleanFilename($uploader->file->getName());

				$uploader->file->save($folderPath.$fileName);

				// Test if we will be able to perform image actions on this image
				if (!craft()->images->setMemoryForImage($folderPath.$fileName))
				{
					IOHelper::deleteFile($folderPath.$fileName);
					$this->returnErrorJson(Craft::t('The uploaded image is too large'));
				}

				craft()->images->cleanImage($folderPath.$fileName);

				$constraint = 500;
				list ($width, $height) = getimagesize($folderPath.$fileName);

				// If the file is in the format badscript.php.gif perhaps.
				if ($width && $height)
				{
					// Never scale up the images, so make the scaling factor always <= 1
					$factor = min($constraint / $width, $constraint / $height, 1);

					$html = craft()->templates->render('_components/tools/cropper_modal',
						array(
							'imageUrl' => UrlHelper::getResourceUrl('userphotos/temp/'.$user->username.'/'.$fileName),
							'width' => round($width * $factor),
							'height' => round($height * $factor),
							'factor' => $factor
						)
					);

					$this->returnJson(array('html' => $html));
				}
			}
		}
		catch (Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}

		$this->returnErrorJson(Craft::t('There was an error uploading your photo'));
	}

	/**
	 * Crop user photo.
	 */
	public function actionCropUserPhoto()
	{
		$this->requireAjaxRequest();
		craft()->userSession->requireLogin();

		$userId = craft()->request->getRequiredPost('userId');

		if ($userId != craft()->userSession->getUser()->id)
		{
			craft()->userSession->requirePermission('editUsers');
		}

		try
		{
			$x1 = craft()->request->getRequiredPost('x1');
			$x2 = craft()->request->getRequiredPost('x2');
			$y1 = craft()->request->getRequiredPost('y1');
			$y2 = craft()->request->getRequiredPost('y2');
			$source = craft()->request->getRequiredPost('source');

			// Strip off any querystring info, if any.
			if (($qIndex = strpos($source, '?')) !== false)
			{
				$source = substr($source, 0, strpos($source, '?'));
			}

			$user = craft()->users->getUserById($userId);

			// make sure that this is this user's file
			$imagePath = craft()->path->getTempUploadsPath().'userphotos/'.$user->username.'/'.$source;

			if (IOHelper::fileExists($imagePath) && craft()->images->setMemoryForImage($imagePath))
			{
				craft()->users->deleteUserPhoto($user);

				if (craft()->users->cropAndSaveUserPhoto($imagePath, $x1, $x2, $y1, $y2, $user))
				{
					IOHelper::clearFolder(craft()->path->getTempUploadsPath().'userphotos/'.$user->username);

					$html = craft()->templates->render('users/_edit/_userphoto',
						array(
							'account' => $user
						)
					);

					$this->returnJson(array('html' => $html));
				}
			}
			IOHelper::clearFolder(craft()->path->getTempUploadsPath().'userphotos/'.$user->username);
		}
		catch (Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}

		$this->returnErrorJson(Craft::t('Something went wrong when processing the photo.'));
	}

	/**
	 * Delete all the photos for current user.
	 */
	public function actionDeleteUserPhoto()
	{
		$this->requireAjaxRequest();
		craft()->userSession->requireLogin();
		$userId = craft()->request->getRequiredPost('userId');

		if ($userId != craft()->userSession->getUser()->id)
		{
			craft()->userSession->requirePermission('editUsers');
		}

		$user = craft()->users->getUserById($userId);
		craft()->users->deleteUserPhoto($user);

		$record = UserRecord::model()->findById($user->id);
		$record->photo = null;
		$record->save();

		// Since Model still believes it has an image, we make sure that it does not so anymore when it reaches the template.
		$user->photo = null;

		$html = craft()->templates->render('users/_edit/_userphoto',
			array(
				'account' => $user
			)
		);

		$this->returnJson(array('html' => $html));
	}

	/**
	 * Saves a user's admin settings.
	 */
	public function actionSaveUserGroups()
	{
		$this->requirePostRequest();
		craft()->userSession->requirePermission('administrateUsers');

		$userId = craft()->request->getRequiredPost('userId');
		$groupIds = craft()->request->getPost('groups');

		craft()->userGroups->assignUserToGroups($userId, $groupIds);

		craft()->userSession->setNotice(Craft::t('User groups saved.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Saves a user's admin settings.
	 */
	public function actionSaveUserPermissions()
	{
		$this->requirePostRequest();
		craft()->userSession->requirePermission('administrateUsers');

		$userId = craft()->request->getRequiredPost('userId');
		$user = craft()->users->getUserById($userId);

		// Only admins can toggle admin settings
		if (craft()->userSession->isAdmin())
		{
			$user->admin = (bool)craft()->request->getPost('admin');
		}

		craft()->users->saveUser($user);

		// Update the user permissions
		if ($user->admin)
		{
			$permissions = array();
		}
		else
		{
			$permissions = craft()->request->getPost('permissions');
		}

		craft()->userPermissions->saveUserPermissions($userId, $permissions);

		craft()->userSession->setNotice(Craft::t('Permissions saved.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Sends a new verification email to a user.
	 */
	public function actionSendVerificationEmail()
	{
		$this->requirePostRequest();
		craft()->userSession->requirePermission('administrateUsers');

		$userId = craft()->request->getRequiredPost('userId');
		$user = craft()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		craft()->users->sendVerificationEmail($user);

		craft()->userSession->setNotice(Craft::t('Verification email sent.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Activates a user, bypassing email verification.
	 */
	public function actionActivateUser()
	{
		$this->requirePostRequest();
		craft()->userSession->requirePermission('administrateUsers');

		$userId = craft()->request->getRequiredPost('userId');
		$user = craft()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		craft()->users->activateUser($user);

		craft()->userSession->setNotice(Craft::t('User activated.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Unlocks a user, bypassing the cooldown phase.
	 */
	public function actionUnlockUser()
	{
		$this->requirePostRequest();
		craft()->userSession->requirePermission('administrateUsers');

		$userId = craft()->request->getRequiredPost('userId');
		$user = craft()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		craft()->users->unlockUser($user);

		craft()->userSession->setNotice(Craft::t('User activated.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Suspends a user.
	 */
	public function actionSuspendUser()
	{
		$this->requirePostRequest();
		craft()->userSession->requirePermission('administrateUsers');

		$userId = craft()->request->getRequiredPost('userId');
		$user = craft()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		craft()->users->suspendUser($user);

		craft()->userSession->setNotice(Craft::t('User suspended.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Unsuspends a user.
	 */
	public function actionUnsuspendUser()
	{
		$this->requirePostRequest();
		craft()->userSession->requirePermission('administrateUsers');

		$userId = craft()->request->getRequiredPost('userId');
		$user = craft()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		craft()->users->unsuspendUser($user);

		craft()->userSession->setNotice(Craft::t('User unsuspended.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Archives a user.
	 */
	public function actionArchiveUser()
	{
		$this->requirePostRequest();
		craft()->userSession->requirePermission('administrateUsers');

		$userId = craft()->request->getRequiredPost('userId');
		$user = craft()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		craft()->users->archiveUser($user);

		craft()->userSession->setNotice(Craft::t('User deleted.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Saves the asset field layout.
	 */
	public function actionSaveFieldLayout()
	{
		$this->requirePostRequest();
		craft()->userSession->requireAdmin();

		// Set the field layout
		$fieldLayout = craft()->fields->assembleLayoutFromPost(false);
		$fieldLayout->type = ElementType::User;
		craft()->fields->deleteLayoutsByType(ElementType::User);

		if (craft()->fields->saveLayout($fieldLayout, false))
		{
			craft()->userSession->setNotice(Craft::t('User fields saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save user fields.'));
		}
	}

	/**
	 * Throws a "no user exists" exception
	 *
	 * @access private
	 * @param int $userId
	 * @throws Exception
	 */
	private function _noUserExists($userId)
	{
		throw new Exception(Craft::t('No user exists with the ID “{id}”.', array('id' => $userId)));
	}
}
