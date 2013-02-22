<?php
namespace Blocks;

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
		if (blx()->userSession->isLoggedIn())
		{
			$this->redirect('');
		}

		$vars = array();

		if (blx()->request->isPostRequest())
		{
			$loginName = blx()->request->getPost('loginName');
			$password = blx()->request->getPost('password');
			$rememberMe = (bool) blx()->request->getPost('rememberMe');

			if (blx()->userSession->login($loginName, $password, $rememberMe))
			{
				if (blx()->request->isAjaxRequest())
				{
					$this->returnJson(array(
						'success' => true
					));
				}
				else
				{
					blx()->userSession->setNotice(Blocks::t('Logged in.'));
					$this->redirectToPostedUrl();
				}
			}
			else
			{
				$errorCode = blx()->userSession->getLoginErrorCode();

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
						$user = blx()->users->getUserByUsernameOrEmail($loginName);
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
					case UserIdentity::ERROR_NO_CP_OFFLINE_ACCESS:
					{
						$error = Blocks::t('You cannot access the CP while the system is offline with that account.');
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
					blx()->userSession->setError($error);

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
		blx()->userSession->logout();
		$this->redirect('');
	}

	/**
	 * Sends a Forgot Password email.
	 */
	public function actionForgotPassword()
	{
		$this->requirePostRequest();

		$loginName = blx()->request->getRequiredPost('loginName');

		$user = blx()->users->getUserByUsernameOrEmail($loginName);

		if ($user)
		{
			if (blx()->users->sendForgotPasswordEmail($user))
			{
				if (blx()->request->isAjaxRequest())
				{
					$this->returnJson(array('success' => true));
				}
				else
				{
					blx()->userSession->setNotice(Blocks::t('Check your email for instructions to reset your password.'));
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
			blx()->userSession->setError($error);
			$this->renderRequestedTemplate();
		}
	}

	/**
	 * Resets a user's password once they've verified they have access to their email.
	 */
	public function actionResetPassword()
	{
		if (blx()->userSession->isLoggedIn())
		{
			$this->redirect('');
		}

		if (blx()->request->isPostRequest())
		{
			$this->requirePostRequest();

			$code = blx()->request->getRequiredPost('code');
			$id = blx()->request->getRequiredPost('id');
			$user = blx()->users->getUserByVerificationCodeAndUid($code, $id);

			if (!$user)
			{
				throw new Exception(Blocks::t('Invalid verification code.'));
			}

			$newPassword = blx()->request->getRequiredPost('newPassword');
			$user->newPassword = $newPassword;

			if (blx()->users->changePassword($user))
			{
				// Log them in
				blx()->userSession->login($user->username, $newPassword);

				blx()->userSession->setNotice(Blocks::t('Password updated.'));
				$this->redirectToPostedUrl();
			}
			else
			{
				blx()->userSession->setNotice(Blocks::t('Couldn’t update password.'));

				$this->renderRequestedTemplate(array(
					'errors' => $user->getErrors('newPassword'),
					'code' => $code,
					'id' => $id
				));
			}
		}
		else
		{
			$code = blx()->request->getQuery('code');
			$id = blx()->request->getQuery('id');
			$user = blx()->users->getUserByVerificationCodeAndUid($code, $id);

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

		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			$userId = blx()->request->getPost('userId');

			if ($userId)
			{
				blx()->userSession->requireLogin();
			}
		}
		else
		{
			blx()->userSession->requireLogin();
			$userId = blx()->userSession->getUser()->id;
		}

		if ($userId)
		{
			if ($userId != blx()->userSession->getUser()->id)
			{
				blx()->userSession->requirePermission('editUsers');
			}

			$user = blx()->users->getUserById($userId);

			if (!$user)
			{
				throw new Exception(Blocks::t('No user exists with the ID “{id}”.', array('id' => $userId)));
			}
		}
		else
		{
			if (!blx()->systemSettings->getSetting('users', 'allowPublicRegistration', false))
			{
				blx()->userSession->requirePermission('registerUsers');
			}

			$user = new UserModel();
		}

		$user->username        = blx()->request->getPost('username');
		$user->firstName       = blx()->request->getPost('firstName');
		$user->lastName        = blx()->request->getPost('lastName');
		$user->email           = blx()->request->getPost('email');
		$user->emailFormat     = blx()->request->getPost('emailFormat');
		$user->preferredLocale = blx()->request->getPost('preferredLocale');

		// Only admins can opt out of requiring email verification
		if (!$user->id)
		{
			if (blx()->userSession->isAdmin())
			{
				$user->verificationRequired = (bool) blx()->request->getPost('verificationRequired');
			}
			else
			{
				$user->verificationRequired = true;
			}
		}

		// Only admins can change other users' passwords
		if (!$user->id || $user->isCurrent() || blx()->userSession->isAdmin())
		{
			$user->newPassword = blx()->request->getPost('newPassword');
		}

		// Only admins can require users to reset their passwords
		if (blx()->userSession->isAdmin())
		{
			$user->passwordResetRequired = (bool)blx()->request->getPost('passwordResetRequired');
		}

		if (blx()->users->saveUser($user))
		{
			blx()->userSession->setNotice(Blocks::t('User saved.'));
			$this->redirectToPostedUrl(array(
				'userId' => $user->id
			));
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save user.'));
			$this->renderRequestedTemplate(array(
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

		$userId = blx()->request->getRequiredPost('userId');

		if ($userId != blx()->userSession->getUser()->id)
		{
			blx()->userSession->requirePermission('editUsers');
		}

		$user = blx()->users->getUserById($userId);

		if (!$user)
		{
			throw new Exception(Blocks::t('No user exists with the ID “{id}”.', array('id' => $userId)));
		}

		$fields = blx()->request->getPost('fields', array());
		$user->setContent($fields);

		if (blx()->users->saveProfile($user))
		{
			blx()->userSession->setNotice(Blocks::t('Profile saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save profile.'));
		}

		$this->renderRequestedTemplate(array(
			'account' => $user,
		));
	}

	/**
	 * Upload a user photo.
	 */
	public function actionUploadUserPhoto()
	{
		$this->requireAjaxRequest();
		blx()->userSession->requireLogin();
		$userId = blx()->request->getRequiredQuery('userId');

		if ($userId != blx()->userSession->getUser()->id)
		{
			blx()->userSession->requirePermission('editUsers');
		}

		// Upload the file and drop it in the temporary folder
		$uploader = new \qqFileUploader();

		try
		{
			// Make sure a file was uploaded
			if ($uploader->file && $uploader->file->getSize())
			{
				$user = blx()->users->getUserById($userId);

				$folderPath = blx()->path->getTempUploadsPath().'userphotos/'.$user->username.'/';

				IOHelper::clearFolder($folderPath);

				IOHelper::ensureFolderExists($folderPath);
				$fileName = IOHelper::cleanFilename($uploader->file->getName());

				$uploader->file->save($folderPath.$fileName);

				// Test if we will be able to perform image actions on this image
				if (!blx()->images->setMemoryForImage($folderPath.$fileName))
				{
					IOHelper::deleteFile($folderPath.$fileName);
					$this->returnErrorJson(Blocks::t('The uploaded image is too large'));
				}

				blx()->images->cleanImage($folderPath.$fileName);

				$constraint = 500;
				list ($width, $height) = getimagesize($folderPath.$fileName);

				// If the file is in the format badscript.php.gif perhaps.
				if ($width && $height)
				{
					// Never scale up the images, so make the scaling factor always <= 1
					$factor = min($constraint / $width, $constraint / $height, 1);

					$html = blx()->templates->render('_components/tools/cropper_modal',
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

		$this->returnErrorJson(Blocks::t('There was an error uploading your photo'));
	}

	/**
	 * Crop user photo.
	 */
	public function actionCropUserPhoto()
	{
		$this->requireAjaxRequest();
		blx()->userSession->requireLogin();

		$userId = blx()->request->getRequiredPost('userId');

		if ($userId != blx()->userSession->getUser()->id)
		{
			blx()->userSession->requirePermission('editUsers');
		}

		try
		{
			$x1 = blx()->request->getRequiredPost('x1');
			$x2 = blx()->request->getRequiredPost('x2');
			$y1 = blx()->request->getRequiredPost('y1');
			$y2 = blx()->request->getRequiredPost('y2');
			$source = blx()->request->getRequiredPost('source');

			// Strip off any querystring info, if any.
			if (($qIndex = strpos($source, '?')) !== false)
			{
				$source = substr($source, 0, strpos($source, '?'));
			}

			$user = blx()->users->getUserById($userId);

			// make sure that this is this user's file
			$imagePath = blx()->path->getTempUploadsPath().'userphotos/'.$user->username.'/'.$source;

			if (IOHelper::fileExists($imagePath) && blx()->images->setMemoryForImage($imagePath))
			{
				blx()->users->deleteUserPhoto($user);

				if (blx()->users->cropAndSaveUserPhoto($imagePath, $x1, $x2, $y1, $y2, $user))
				{
					IOHelper::clearFolder(blx()->path->getTempUploadsPath().'userphotos/'.$user->username);

					$html = blx()->templates->render('users/_edit/_userphoto',
						array(
							'account' => $user
						)
					);

					$this->returnJson(array('html' => $html));
				}
			}
			IOHelper::clearFolder(blx()->path->getTempUploadsPath().'userphotos/'.$user->username);
		}
		catch (Exception $exception)
		{
			$this->returnErrorJson($exception->getMessage());
		}

		$this->returnErrorJson(Blocks::t('Something went wrong when processing the photo.'));
	}

	/**
	 * Delete all the photos for current user.
	 */
	public function actionDeleteUserPhoto()
	{
		$this->requireAjaxRequest();
		blx()->userSession->requireLogin();
		$userId = blx()->request->getRequiredPost('userId');

		if ($userId != blx()->userSession->getUser()->id)
		{
			blx()->userSession->requirePermission('editUsers');
		}

		$user = blx()->users->getUserById($userId);
		blx()->users->deleteUserPhoto($user);

		$record = UserRecord::model()->findById($user->id);
		$record->photo = null;
		$record->save();

		// Since Model still believes it has an image, we make sure that it does not so anymore when it reaches the template.
		$user->photo = null;

		$html = blx()->templates->render('users/_edit/_userphoto',
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
		blx()->userSession->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$groupIds = blx()->request->getPost('groups');

		blx()->userGroups->assignUserToGroups($userId, $groupIds);

		blx()->userSession->setNotice(Blocks::t('User groups saved.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Saves a user's admin settings.
	 */
	public function actionSaveUserPermissions()
	{
		$this->requirePostRequest();
		blx()->userSession->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$user = blx()->users->getUserById($userId);

		// Only admins can toggle admin settings
		if (blx()->userSession->isAdmin())
		{
			$user->admin = (bool)blx()->request->getPost('admin');
		}

		blx()->users->saveUser($user);

		// Update the user permissions
		if ($user->admin)
		{
			$permissions = array();
		}
		else
		{
			$permissions = blx()->request->getPost('permissions');
		}

		blx()->userPermissions->saveUserPermissions($userId, $permissions);

		blx()->userSession->setNotice(Blocks::t('Permissions saved.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Sends a new verification email to a user.
	 */
	public function actionSendVerificationEmail()
	{
		$this->requirePostRequest();
		blx()->userSession->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$user = blx()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		blx()->users->sendVerificationEmail($user);

		blx()->userSession->setNotice(Blocks::t('Verification email sent.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Activates a user, bypassing email verification.
	 */
	public function actionActivateUser()
	{
		$this->requirePostRequest();
		blx()->userSession->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$user = blx()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		blx()->users->activateUser($user);

		blx()->userSession->setNotice(Blocks::t('User activated.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Unlocks a user, bypassing the cooldown phase.
	 */
	public function actionUnlockUser()
	{
		$this->requirePostRequest();
		blx()->userSession->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$user = blx()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		blx()->users->unlockUser($user);

		blx()->userSession->setNotice(Blocks::t('User activated.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Suspends a user.
	 */
	public function actionSuspendUser()
	{
		$this->requirePostRequest();
		blx()->userSession->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$user = blx()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		blx()->users->suspendUser($user);

		blx()->userSession->setNotice(Blocks::t('User suspended.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Unsuspends a user.
	 */
	public function actionUnsuspendUser()
	{
		$this->requirePostRequest();
		blx()->userSession->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$user = blx()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		blx()->users->unsuspendUser($user);

		blx()->userSession->setNotice(Blocks::t('User unsuspended.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Archives a user.
	 */
	public function actionArchiveUser()
	{
		$this->requirePostRequest();
		blx()->userSession->requirePermission('administrateUsers');

		$userId = blx()->request->getRequiredPost('userId');
		$user = blx()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		blx()->users->archiveUser($user);

		blx()->userSession->setNotice(Blocks::t('User deleted.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Saves the asset field layout.
	 */
	public function actionSaveFieldLayout()
	{
		$this->requirePostRequest();
		blx()->userSession->requireAdmin();

		// Set the field layout
		$fieldLayout = blx()->fields->assembleLayoutFromPost(false);
		$fieldLayout->type = ElementType::User;
		blx()->fields->deleteLayoutsByType(ElementType::User);

		if (blx()->fields->saveLayout($fieldLayout, false))
		{
			blx()->userSession->setNotice(Blocks::t('User fields saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save user fields.'));
		}

		$this->renderRequestedTemplate();
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
		throw new Exception(Blocks::t('No user exists with the ID “{id}”.', array('id' => $userId)));
	}
}
