<?php
namespace Craft;

/**
 * Handles user account related tasks.
 *
 * @package craft.app.controllers
 */
class UsersController extends BaseController
{
	protected $allowAnonymous = array('actionLogin', 'actionForgotPassword', 'actionValidate', 'actionSetPassword', 'actionSaveUser');

	/**
	 * Displays the login template, and handles login post requests.
	 */
	public function actionLogin()
	{
		if (craft()->userSession->isLoggedIn())
		{
			if (craft()->request->isAjaxRequest())
			{
				$this->returnJson(array(
					'success' => true
				));
			}
			else
			{
				// They are already logged in.
				$currentUser = craft()->userSession->getUser();

				// If they can access the control panel, redirect them to the dashboard.
				if ($currentUser->can('accessCp'))
				{
					$this->redirect(UrlHelper::getCpUrl('dashboard'));
				}
				else
				{
					// Already logged in, but can't access the CP?  Send them to the front-end home page.
					$this->redirect(UrlHelper::getSiteUrl());
				}
			}
		}

		if (craft()->request->isPostRequest())
		{
			// A little house-cleaning for expired, pending users, first.
			craft()->users->purgeExpiredPendingUsers();

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
				$errorMessage = craft()->userSession->getLoginErrorMessage($errorCode, $loginName);

				if (craft()->request->isAjaxRequest())
				{
					$this->returnJson(array(
						'errorCode' => $errorCode,
						'error' => $errorMessage
					));
				}
				else
				{
					craft()->userSession->setError($errorMessage);

					craft()->urlManager->setRouteVariables(array(
						'loginName' => $loginName,
						'rememberMe' => $rememberMe,
						'errorCode' => $errorCode,
						'errorMessage' => $errorMessage,
					));
				}
			}
		}
	}

	/**
	 * Logs a user in for impersonation.  Requires you to be an administrator.
	 */
	public function actionImpersonate()
	{
		$this->requireLogin();
		$this->requireAdmin();
		$this->requirePostRequest();

		$userId = craft()->request->getPost('userId');

		if (craft()->userSession->impersonate($userId))
		{
			craft()->userSession->setNotice(Craft::t('Logged in.'));

			if (craft()->userSession->getUser()->can('accessCp'))
			{
				$this->redirect(UrlHelper::getCpUrl('dashboard'));
			}
			else
			{
				$this->redirect(UrlHelper::getSiteUrl(''));
			}
		}
		else
		{
			craft()->userSession->setError(Craft::t('There was a problem impersonating this user.'));
			Craft::log(craft()->userSession->getUser()->username.' tried to impersonate userId: '.$userId.' but something went wrong.', LogLevel::Error);
		}
	}

	/**
	 *
	 */
	public function actionLogout()
	{
		craft()->userSession->logout(false);
		$this->redirect('');
	}

	/**
	 * Sends a Forgot Password email.
	 */
	public function actionForgotPassword()
	{
		$this->requirePostRequest();

		$loginName = craft()->request->getPost('loginName');
		$errors = array();

		if (!$loginName)
		{
			$errors[] = Craft::t('Username or email is required.');
		}
		else
		{
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
					$errors[] = Craft::t('There was a problem sending the forgot password email.');
				}
			}
			else
			{
				$errors[] = Craft::t('Invalid username or email.');
			}
		}

		if (craft()->request->isAjaxRequest())
		{
			$this->returnErrorJson($errors);
		}
		else
		{
			// Send the data back to the template
			craft()->urlManager->setRouteVariables(array(
				'errors' => $errors,
				'loginName'   => $loginName,
			));
		}
	}

	/**
	 * Sets a user's password once they've verified they have access to their email.
	 *
	 * @throws HttpException
	 * @throws Exception
	 */
	public function actionSetPassword()
	{
		if (craft()->userSession->isLoggedIn())
		{
			craft()->userSession->logout();
		}

		$code = craft()->request->getRequiredParam('code');
		$id = craft()->request->getRequiredParam('id');
		$user = craft()->users->getUserByUid($id);

		if ($user)
		{
			$isCodeValid = craft()->users->isVerificationCodeValidForUser($user, $code);
		}

		if (!$user || !$isCodeValid)
		{
			throw new HttpException('200', Craft::t('Invalid verification code.'));
		}

		$url = craft()->config->getSetPasswordPath($code, $id, $user);

		if (craft()->request->isPostRequest())
		{
			$newPassword = craft()->request->getRequiredPost('newPassword');

			$passwordModel = new PasswordModel();
			$passwordModel->password = $newPassword;

			if ($passwordModel->validate())
			{
				$user->newPassword = $newPassword;

				if (craft()->users->changePassword($user))
				{
					// Do we need to auto-login?
					if (craft()->config->get('autoLoginAfterAccountActivation') === true)
					{
						craft()->userSession->impersonate($user->id);
					}

					// If the user can't access the CP, then send them to the front-end setPasswordSuccessPath.
					if (!$user->can('accessCp'))
					{
						$url = UrlHelper::getSiteUrl(craft()->config->getLocalized('setPasswordSuccessPath'));
						$this->redirect($url);
					}
					else
					{
						craft()->userSession->setNotice(Craft::t('Password updated.'));
						$url = UrlHelper::getCpUrl('dashboard');
						$this->redirect($url);
					}
				}
			}

			craft()->userSession->setNotice(Craft::t('Couldn’t update password.'));

			$this->_processSetPasswordPath($user);

			$errors = array();
			$errors = array_merge($errors, $user->getErrors('newPassword'));
			$errors = array_merge($errors, $passwordModel->getErrors('password'));

			$this->renderTemplate($url, array(
				'errors' => $errors,
				'code' => $code,
				'id' => $id,
				'newUser' => ($user->password ? false : true),
			));
		}
		else
		{
			$this->_processSetPasswordPath($user);

			$this->renderTemplate($url, array(
				'code' => $code,
				'id' => $id,
				'newUser' => ($user->password ? false : true),
			));
		}
	}

	/**
	 * Validate that a user has access to an email address.
	 *
	 * @throws HttpException
	 * @throws Exception
	 */
	public function actionValidate()
	{
		$code = craft()->request->getRequiredQuery('code');
		$id = craft()->request->getRequiredQuery('id');
		$userToValidate = craft()->users->getUserByUid($id);
		$isCodeValid = false;

		if ($userToValidate)
		{
			$isCodeValid = craft()->users->isVerificationCodeValidForUser($userToValidate, $code);
		}

		if (!$userToValidate || !$isCodeValid)
		{
			if (($url = craft()->config->getLocalized('activateAccountFailurePath')) != '')
			{
				$this->redirect(UrlHelper::getSiteUrl($url));
			}
			else
			{
				if (!$userToValidate)
				{
					throw new HttpException('200', Craft::t('Invalid verification code.'));
				}
				else
				{
					craft()->path->setTemplatesPath(craft()->path->getCpTemplatesPath());

					$this->renderTemplate('_special/expired', array('userId' => $userToValidate->id));
				}
			}
		}
		else
		{
			// If the current user is logged in
			if (($currentUser = craft()->userSession->getUser()) !== null)
			{
				// If they are validating an account that doesn't belong to them, log them out of their current account.
				if ($currentUser->id !== $userToValidate->id)
				{
					craft()->userSession->logout();
				}
			}

			if (craft()->users->activateUser($userToValidate))
			{
				// Successfully activated user, do they require a password reset or is their password empty? If so, send them through the password logic.
				if ($userToValidate->passwordResetRequired || !$userToValidate->password)
				{
					// All users that go through account activation will need to set their password.
					$code = craft()->users->setVerificationCodeOnUser($userToValidate);

					if ($userToValidate->can('accessCp'))
					{
						$url = craft()->config->get('actionTrigger').'/users/'.craft()->config->getCpSetPasswordPath();
					}
					else
					{
						$url = craft()->config->getLocalized('setPasswordPath');
					}
				}
				else
				{
					// Do we need to auto-login?
					if ( craft()->config->get('autoLoginAfterAccountActivation') === true)
					{
						craft()->userSession->impersonate($userToValidate->id);
					}

					// If the user can't access the CP, then send them to the front-end activateAccountSuccessPath.
					if (!$userToValidate->can('accessCp'))
					{
						$url = UrlHelper::getUrl(craft()->config->getLocalized('activateAccountSuccessPath'));
						$this->redirect($url);
					}
					else
					{
						craft()->userSession->setNotice(Craft::t('Account activated.'));

						if (craft()->request->getPost('redirect'))
						{
							$this->redirectToPostedUrl();
						}
						else
						{
							$this->redirect(UrlHelper::getCpUrl());
						}
					}
				}
			}
			else
			{
				$url = craft()->config->getLocalized('activateAccountFailurePath');

				if ($url === '')
				{
					// Failed to validate user and there is no custom validation failure path.  Throw an exception.
					throw new HttpException('200', Craft::t('There was a problem activating this account.'));
				}
				else
				{
					// Failed to activate user and there is a custom validate failure path set, so use it.
					$url = UrlHelper::getSiteUrl($url);
				}
			}

			if (craft()->request->isSecureConnection())
			{
				$url = UrlHelper::getUrl($url, array(
					'code' => $code, 'id' => $id
				), 'https');
			}

			$url = UrlHelper::getUrl($url, array(
				'code' => $code, 'id' => $id
			));

			$this->redirect($url);
		}
	}

	/**
	 * Manually activates a user account.  Only admins have access.
	 */
	public function actionActivateUser()
	{
		$this->requireAdmin();
		$this->requirePostRequest();

		$userId = craft()->request->getRequiredPost('userId');
		$user = craft()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		if (craft()->users->activateUser($user))
		{
			craft()->userSession->setNotice(Craft::t('Successfully activated the user.'));
		}
		else
		{
			craft()->userSession->setError(Craft::t('There was a problem activating the user.'));
		}

		$this->redirectToPostedUrl();
	}

	/**
	 * Edit a user account.
	 *
	 * @param array $variables
	 * @param string|null $account
	 * @throws HttpException
	 */
	public function actionEditUser(array $variables = array(), $account = null)
	{
		$isClientAccount = false;

		// This will be set if there was a validation error.
		if (empty($variables['account']))
		{
			// Are we editing a specific user account?
			if ($account !== null)
			{
				switch ($account)
				{
					case 'current':
					{
						$variables['account'] = craft()->userSession->getUser();

						break;
					}
					case 'client':
					{
						$isClientAccount = true;
						$variables['account'] = craft()->users->getClient();

						if (!$variables['account'])
						{
							// Registering the Client
							$variables['account'] = new UserModel();
							$variables['account']->client = true;
						}

						break;
					}
					default:
					{
						throw new HttpException(404);
					}
				}
			}
			else if (!empty($variables['userId']))
			{
				$variables['account'] = craft()->users->getUserById($variables['userId']);

				if (!$variables['account'])
				{
					throw new HttpException(404);
				}
			}
			else if (craft()->getEdition() == Craft::Pro)
			{
				// Registering a new user
				$variables['account'] = new UserModel();
			}
			else
			{
				// Nada.
				throw new HttpException(404);
			}
		}

		$variables['isNewAccount'] = !$variables['account']->id;

		// Set the appropriate page title
		if (!$variables['isNewAccount'])
		{
			if ($variables['account']->isCurrent())
			{
				$variables['title'] = Craft::t('My Account');
			}
			else
			{
				craft()->userSession->requirePermission('editUsers');

				$variables['title'] = Craft::t("{user}’s Account", array('user' => $variables['account']->name));
			}
		}
		else if ($isClientAccount)
		{
			$variables['title'] = Craft::t('Register the client’s account');
		}
		else
		{
			// New user, make sure we can register.
			craft()->userSession->requirePermission('registerUsers');

			$variables['title'] = Craft::t("Register a new user");
		}

		// Show tabs if they have Craft Pro
		if (craft()->getEdition() == Craft::Pro)
		{
			$variables['selectedTab'] = 'account';

			$variables['tabs'] = array(
				'account' => array(
					'label' => Craft::t('Account'),
					'url'   => '#account',
				)
			);

			// No need to show the Profile tab if it's a new user (can't have an avatar yet) and there's no user fields
			if (!$variables['isNewAccount'] || $variables['account']->getFieldLayout()->getFields())
			{
				$variables['tabs']['profile'] = array(
					'label' => Craft::t('Profile'),
					'url'   => '#profile',
				);
			}

			// If they can assign user groups and permissions, show the Permissions tab
			if (craft()->userSession->getUser()->can('assignUserPermissions'))
			{
				$variables['tabs']['perms'] = array(
					'label' => Craft::t('Permissions'),
					'url'   => '#perms',
				);
			}
		}
		else
		{
			$variables['tabs'] = array();
		}

		// Ugly.  But Users don't have a real fieldlayout/tabs.
		$accountFields = array('username', 'firstName', 'lastName', 'email', 'password', 'newPassword', 'currentPassword', 'passwordResetRequired', 'preferredLocale');

		if (craft()->getEdition() == Craft::Pro && $variables['account']->hasErrors())
		{
			$errors = $variables['account']->getErrors();

			foreach ($errors as $attribute => $error)
			{
				if (in_array($attribute, $accountFields))
				{
					$variables['tabs']['account']['class'] = 'error';
				}
				else if (isset($variables['tabs']['profile']))
				{
					$variables['tabs']['profile']['class'] = 'error';
				}
			}
		}

		craft()->templates->includeCssResource('css/account.css');
		craft()->templates->includeJsResource('js/account.js');

		$this->renderTemplate('users/_edit', $variables);
	}

	/**
	 * Registers a new user, or saves an existing user's account settings.
	 */
	public function actionSaveUser()
	{
		$this->requirePostRequest();

		$currentUser = craft()->userSession->getUser();
		$thisIsPublicRegistration = false;

		$userId = craft()->request->getPost('userId');
		$isNewUser = $userId === null ? true : false;

		// Are we editing an existing user?
		if ($userId)
		{
			$user = craft()->users->getUserById($userId);

			if (!$user)
			{
				throw new Exception(Craft::t('No user exists with the ID “{id}”.', array('id' => $userId)));
			}

			if (!$user->isCurrent())
			{
				// Make sure they have permission to edit other users
				craft()->userSession->requirePermission('editUsers');
			}
		}
		else if (craft()->getEdition() == Craft::Client)
		{
			// Make sure they're logged in
			craft()->userSession->requireAdmin();

			// Make sure there's no Client user yet
			if (craft()->users->getClient())
			{
				throw new Exception(Craft::t('A client account already exists.'));
			}

			$user = new UserModel();
			$user->client = true;
		}
		else
		{
			// Make sure this is Craft Pro, since that's required for having multiple user accounts
			craft()->requireEdition(Craft::Pro);

			// Is someone logged in?
			if ($currentUser)
			{
				// Make sure they have permission to register users
				craft()->userSession->requirePermission('registerUsers');
			}
			else
			{
				// Make sure public registration is allowed
				if (!craft()->systemSettings->getSetting('users', 'allowPublicRegistration'))
				{
					throw new HttpException(403);
				}

				$thisIsPublicRegistration = true;
			}

			$user = new UserModel();
		}

		// Should we check for a new email and password?
		if ($isNewUser || $user->isCurrent() || craft()->userSession->isAdmin())
		{
			$newEmail    = craft()->request->getPost('email');
			$newPassword = craft()->request->getPost($user->id ? 'newPassword' : 'password');

			if ($user->id && $user->email == $newEmail)
			{
				$newEmail = null;
			}

			// You must pass your current password to change these fields for an existing user
			if (!$isNewUser && ($newEmail || $newPassword))
			{
				// Make sure the correct current password has been submitted
				$currentPassword = craft()->request->getPost('password');
				$currentHashedPassword = $currentUser->password;

				if (!craft()->users->validatePassword($currentHashedPassword, $currentPassword))
				{
					Craft::log('Tried to change the email or password for userId: '.$user->id.', but the current password does not match what the user supplied.', LogLevel::Warning);
					$user->addError('currentPassword', Craft::t('Incorrect current password.'));

					// We'll let the script keep executing in case we find any other validation errors...
				}
			}

			if ($thisIsPublicRegistration || $newPassword)
			{
				// Make sure it's valid
				$passwordModel = new PasswordModel();
				$passwordModel->password = $newPassword;

				if ($passwordModel->validate())
				{
					$user->newPassword = $newPassword;
				}
				else
				{
					$user->addError('password', $passwordModel->getError('password'));
				}
			}

			if ($newEmail)
			{
				// Does that email need to be verified?
				if (craft()->systemSettings->getSetting('users', 'requireEmailVerification') && (!craft()->userSession->isAdmin() || craft()->request->getPost('verificationRequired')))
				{
					$user->unverifiedEmail = $newEmail;

					if ($isNewUser)
					{
						// Set it as the main email too
						$user->email = $newEmail;
					}
				}
				else
				{
					$user->email = $newEmail;
				}
			}
		}

		if (craft()->config->get('useEmailAsUsername'))
		{
			$user->username    =  $user->email;
		}
		else
		{
			$user->username    = craft()->request->getPost('username', ($user->username ? $user->username : $user->email));
		}

		$user->firstName       = craft()->request->getPost('firstName', $user->firstName);
		$user->lastName        = craft()->request->getPost('lastName', $user->lastName);
		$user->preferredLocale = craft()->request->getPost('preferredLocale', $user->preferredLocale);

		if ($isNewUser)
		{
			if ($user->unverifiedEmail)
			{
				$user->status = UserStatus::Pending;
			}
			else
			{
				$user->status = UserStatus::Active;
			}
		}

		// There are some things only admins can change
		if (craft()->userSession->isAdmin())
		{
			$user->passwordResetRequired = (bool) craft()->request->getPost('passwordResetRequired', $user->passwordResetRequired);
			$user->admin = (bool) craft()->request->getPost('admin', $user->admin);
		}

		// If this is Craft Pro, grab any profile content from post
		if (craft()->getEdition() == Craft::Pro)
		{
			$user->setContentFromPost('fields');
		}

		// Validate and save!
		if ($user->validate(null, false) && craft()->users->saveUser($user))
		{
			$this->_processUserPhoto($user);

			if ($currentUser)
			{
				$this->_processUserGroupsPermissions($user, $currentUser);
			}

			if ($thisIsPublicRegistration)
			{
				// Assign them to the default user group
				$this->_assignDefaultGroupToUser($user->id);
			}

			craft()->userSession->setNotice(Craft::t('User saved.'));

			if (isset($_POST['redirect']) && mb_strpos($_POST['redirect'], '{userId}') !== false)
			{
				craft()->deprecator->log('UsersController::saveUser():userId_redirect', 'The {userId} token within the ‘redirect’ param on users/saveUser requests has been deprecated. Use {id} instead.');
				$_POST['redirect'] = str_replace('{userId}', '{id}', $_POST['redirect']);
			}

			// If this is a new user and you're currently not logged in.
			if ($isNewUser && !$currentUser && $thisIsPublicRegistration && !$user->unverifiedEmail)
			{
				// Do we need to auto-login?
				if ( craft()->config->get('autoLoginAfterAccountActivation') === true)
				{
					craft()->userSession->impersonate($user->id);
				}
			}

			$this->redirectToPostedUrl($user);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save user.'));
		}

		// Send the account back to the template
		craft()->urlManager->setRouteVariables(array(
			'account' => $user
		));
	}

	/**
	 * Saves a user's profile.
	 *
	 * @deprecated Deprecated in 2.0.
	 */
	public function actionSaveProfile()
	{
		craft()->deprecator->log('UsersController::saveProfile()', 'The users/saveProfile action has been deprecated. Use users/saveUser instead.');
		$this->actionSaveUser();
	}

	/**
	 * Upload a user photo.
	 */
	public function actionUploadUserPhoto()
	{
		$this->requireAjaxRequest();
		craft()->userSession->requireLogin();
		$userId = craft()->request->getRequiredPost('userId');

		if ($userId != craft()->userSession->getUser()->id)
		{
			craft()->userSession->requirePermission('editUsers');
		}

		// Upload the file and drop it in the temporary folder
		$file = $_FILES['image-upload'];

		try
		{
			// Make sure a file was uploaded
			if (!empty($file['name']) && !empty($file['size'])  )
			{
				$user = craft()->users->getUserById($userId);
				$userName = IOHelper::cleanFilename($user->username);

				$folderPath = craft()->path->getTempUploadsPath().'userphotos/'.$userName.'/';

				IOHelper::clearFolder($folderPath);

				IOHelper::ensureFolderExists($folderPath);
				$fileName = IOHelper::cleanFilename($file['name']);

				move_uploaded_file($file['tmp_name'], $folderPath.$fileName);

				// Test if we will be able to perform image actions on this image
				if (!craft()->images->checkMemoryForImage($folderPath.$fileName))
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
							'imageUrl' => UrlHelper::getResourceUrl('userphotos/temp/'.$userName.'/'.$fileName),
							'width' => round($width * $factor),
							'height' => round($height * $factor),
							'factor' => $factor,
							'constraint' => $constraint
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
			if (($qIndex = mb_strpos($source, '?')) !== false)
			{
				$source = mb_substr($source, 0, mb_strpos($source, '?'));
			}

			$user = craft()->users->getUserById($userId);
			$userName = IOHelper::cleanFilename($user->username);

			// make sure that this is this user's file
			$imagePath = craft()->path->getTempUploadsPath().'userphotos/'.$userName.'/'.$source;

			if (IOHelper::fileExists($imagePath) && craft()->images->checkMemoryForImage($imagePath))
			{
				craft()->users->deleteUserPhoto($user);

				$image = craft()->images->loadImage($imagePath);
				$image->crop($x1, $x2, $y1, $y2);

				if (craft()->users->saveUserPhoto(IOHelper::getFileName($imagePath), $image, $user))
				{
					IOHelper::clearFolder(craft()->path->getTempUploadsPath().'userphotos/'.$userName);

					$html = craft()->templates->render('users/_userphoto',
						array(
							'account' => $user
						)
					);

					$this->returnJson(array('html' => $html));
				}
			}

			IOHelper::clearFolder(craft()->path->getTempUploadsPath().'userphotos/'.$userName);
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

		$user->photo = null;
		craft()->users->saveUser($user);

		$html = craft()->templates->render('users/_userphoto',
			array(
				'account' => $user
			)
		);

		$this->returnJson(array('html' => $html));
	}

	/**
	 * Sends a new activation email to a user.
	 */
	public function actionSendActivationEmail()
	{
		$this->requirePostRequest();

		$userId = craft()->request->getRequiredPost('userId');
		$user = craft()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		craft()->users->sendActivationEmail($user);

		if (craft()->request->isAjaxRequest())
		{
			die('great!');
		}
		else
		{
			craft()->userSession->setNotice(Craft::t('Activation email sent.'));
			$this->redirectToPostedUrl();
		}
	}

	/**
	 * Unlocks a user, bypassing the cooldown phase.
	 */
	public function actionUnlockUser()
	{
		$this->requirePostRequest();
		$this->requireLogin();
		craft()->userSession->requirePermission('administrateUsers');

		$userId = craft()->request->getRequiredPost('userId');
		$user = craft()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		// Even if you have administrateUsers permissions, only and admin should be able to unlock another admin.
		$currentUser = craft()->userSession->getUser();

		if ($user->admin && !$currentUser->admin)
		{
			throw new HttpException(403);
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
		$this->requireLogin();
		craft()->userSession->requirePermission('administrateUsers');

		$userId = craft()->request->getRequiredPost('userId');
		$user = craft()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		// Even if you have administrateUsers permissions, only and admin should be able to suspend another admin.
		$currentUser = craft()->userSession->getUser();

		if ($user->admin && !$currentUser->admin)
		{
			throw new HttpException(403);
		}

		craft()->users->suspendUser($user);

		craft()->userSession->setNotice(Craft::t('User suspended.'));
		$this->redirectToPostedUrl();
	}

	/**
	 * Deletes a user.
	 */
	public function actionDeleteUser()
	{
		$this->requirePostRequest();
		$this->requireLogin();

		craft()->userSession->requirePermission('deleteUsers');

		$userId = craft()->request->getRequiredPost('userId');
		$user = craft()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		// Even if you have deleteUser permissions, only and admin should be able to delete another admin.
		$currentUser = craft()->userSession->getUser();

		if ($user->admin && !$currentUser->admin)
		{
			throw new HttpException(403);
		}

		craft()->users->deleteUser($user);

		craft()->userSession->setNotice(Craft::t('User deleted.'));
		$this->redirectToPostedUrl();
	}


	/**
	 * Unsuspends a user.
	 */
	public function actionUnsuspendUser()
	{
		$this->requirePostRequest();
		$this->requireLogin();
		craft()->userSession->requirePermission('administrateUsers');

		$userId = craft()->request->getRequiredPost('userId');
		$user = craft()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		// Even if you have administrateUsers permissions, only and admin should be able to unsuspend another admin.
		$currentUser = craft()->userSession->getUser();

		if ($user->admin && !$currentUser->admin)
		{
			throw new HttpException(403);
		}

		craft()->users->unsuspendUser($user);

		craft()->userSession->setNotice(Craft::t('User unsuspended.'));
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
	 * Verifies a password for a user.
	 *
	 * @return bool
	 */
	public function actionVerifyPassword()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$password = craft()->request->getRequiredParam('password');
		$user = craft()->userSession->getUser();

		if ($user)
		{
			if (craft()->users->validatePassword($user->password, $password))
			{
				$this->returnJson(array('success' => true));
			}
		}

		$this->returnErrorJson(Craft::t('Invalid password.'));
	}

	/**
	 * @param $user
	 */
	private function _processSetPasswordPath($user)
	{
		// If the user cannot access the CP
		if (!$user->can('accessCp'))
		{
			// Make sure we're looking at the front-end templates path to start with.
			craft()->path->setTemplatesPath(craft()->path->getSiteTemplatesPath());

			// If they haven't defined a front-end set password template
			if (!craft()->templates->doesTemplateExist(craft()->config->getLocalized('setPasswordPath')))
			{
				// Set PathService to use the CP templates path instead
				craft()->path->setTemplatesPath(craft()->path->getCpTemplatesPath());
			}
		}
		// The user can access the CP, so send them to Craft's set password template in the dashboard.
		else
		{
			craft()->path->setTemplatesPath(craft()->path->getCpTemplatesPath());
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

	/**
	 * @param $userId
	 * @return void
	 */
	private function _assignDefaultGroupToUser($userId)
	{
		// Assign them to the default user group, if any
		$defaultGroup = craft()->systemSettings->getSetting('users', 'defaultGroup');

		if ($defaultGroup)
		{
			craft()->userGroups->assignUserToGroups($userId, array($defaultGroup));
		}
	}

	/**
	 * @param $user
	 */
	private function _processUserPhoto($user)
	{
		// Delete their photo?
		if (craft()->request->getPost('deleteUserPhoto'))
		{
			craft()->users->deleteUserPhoto($user);
		}

		// Did they upload a new one?
		if ($userPhoto = UploadedFile::getInstanceByName('userPhoto'))
		{
			craft()->users->deleteUserPhoto($user);
			$image = craft()->images->loadImage($userPhoto->getTempName());
			$imageWidth = $image->getWidth();
			$imageHeight = $image->getHeight();

			$dimension = min($imageWidth, $imageHeight);
			$horizontalMargin = ($imageWidth - $dimension) / 2;
			$verticalMargin = ($imageHeight - $dimension) / 2;
			$image->crop($horizontalMargin, $imageWidth - $horizontalMargin, $verticalMargin, $imageHeight - $verticalMargin);

			craft()->users->saveUserPhoto($userPhoto->getName(), $image, $user);

			IOHelper::deleteFile($userPhoto->getTempName());
		}
	}

	public function _processUserGroupsPermissions($user, $currentUser)
	{
		// Save any user groups
		if (craft()->getEdition() == Craft::Pro && $currentUser->can('assignUserPermissions'))
		{
			// Save any user groups
			$groupIds = craft()->request->getPost('groups');

			if ($groupIds !== null)
			{
				craft()->userGroups->assignUserToGroups($user->id, $groupIds);
			}

			// Save any user permissions
			if ($user->admin)
			{
				$permissions = array();
			}
			else
			{
				$permissions = craft()->request->getPost('permissions');
			}

			if ($permissions !== null)
			{
				craft()->userPermissions->saveUserPermissions($user->id, $permissions);
			}
		}
	}
}
