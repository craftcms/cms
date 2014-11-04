<?php
namespace Craft;

/**
 * The UsersController class is a controller that handles various user account related tasks such as logging-in,
 * impersonating a user, logging out, forgetting passwords, setting passwords, validating accounts, activating
 * accounts, creating users, saving users, processing user avatars, deleting, suspending and un-suspending users.
 *
 * Note that all actions in the controller, except {@link actionLogin}, {@link actionLogout}, {@link actionGetAuthTimeout},
 * {@link actionForgotPassword}, {@link actionValidate}, {@link actionSetPassword} and {@link actionSaveUser} require an
 * authenticated Craft session via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class UsersController extends BaseController
{
	// Properties
	// =========================================================================

	/**
	 * If set to false, you are required to be logged in to execute any of the given controller's actions.
	 *
	 * If set to true, anonymous access is allowed for all of the given controller's actions.
	 *
	 * If the value is an array of action names, then you must be logged in for any action method except for the ones in
	 * the array list.
	 *
	 * If you have a controller that where the majority of action methods will be anonymous, but you only want require
	 * login on a few, it's best to use {@link UserSessionService::requireLogin() craft()->userSession->requireLogin()}
	 * in the individual methods.
	 *
	 * @var bool
	 */
	protected $allowAnonymous = array('actionLogin', 'actionLogout', 'actionGetAuthTimeout', 'actionForgotPassword', 'actionSendActivationEmail', 'actionSaveUser', 'actionSetPassword', 'actionValidate');

	// Public Methods
	// =========================================================================

	/**
	 * Displays the login template, and handles login post requests.
	 *
	 * @return null
	 */
	public function actionLogin()
	{
		if (craft()->userSession->isLoggedIn())
		{
			// Too easy.
			$this->_handleSuccessfulLogin(false);
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
				$this->_handleSuccessfulLogin(true);
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
	 *
	 * @return null
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

			$this->_handleSuccessfulLogin(true);
		}
		else
		{
			craft()->userSession->setError(Craft::t('There was a problem impersonating this user.'));
			Craft::log(craft()->userSession->getUser()->username.' tried to impersonate userId: '.$userId.' but something went wrong.', LogLevel::Error);
		}
	}

	/**
	 * Returns how many seconds are left in the current user session.
	 *
	 * @return null
	 */
	public function actionGetAuthTimeout()
	{
		echo craft()->userSession->getAuthTimeout();
		craft()->end();
	}

	/**
	 * @return null
	 */
	public function actionLogout()
	{
		craft()->userSession->logout(false);

		if (craft()->request->isAjaxRequest())
		{
			$this->returnJson(array(
				'success' => true
			));
		}
		else
		{
			$this->redirect('');
		}
	}

	/**
	 * Sends a Forgot Password email.
	 *
	 * @return null
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
	 * @throws HttpException|Exception
	 * @return null
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
						$setPasswordSuccessPath = craft()->config->getLocalized('setPasswordSuccessPath');
						$url = UrlHelper::getSiteUrl($setPasswordSuccessPath);
					}
					else
					{
						$postCpLoginRedirect = craft()->config->get('postCpLoginRedirect');
						$url = UrlHelper::getCpUrl($postCpLoginRedirect);
					}

					$this->redirect($url);
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
	 * @throws HttpException|Exception
	 * @return null
	 */
	public function actionValidate()
	{
		$code = craft()->request->getRequiredQuery('code');
		$id = craft()->request->getRequiredQuery('id');
		$userToValidate = craft()->users->getUserByUid($id);
		$isCodeValid = false;

		if ($userToValidate)
		{
			// Fire an 'onBeforeVerifyUser' event
			craft()->users->onBeforeVerifyUser(new Event($this, array(
				'user' => $userToValidate
			)));

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
				// If they are validating an account that doesn't belong to them,
				// log them out of their current account.
				if ($currentUser->id !== $userToValidate->id)
				{
					craft()->userSession->logout();
				}
			}

			if (craft()->users->activateUser($userToValidate))
			{
				// Successfully activated user, do they require a password reset or is their password empty? If so, send
				// them through the password logic.
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
	 *
	 * @return null
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
	 * @param array       $variables
	 * @param string|null $account
	 *
	 * @throws HttpException
	 * @return null
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

			// No need to show the Profile tab if it's a new user (can't have an avatar yet) and there's no user fields.
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
		craft()->templates->includeJsResource('js/AccountSettingsForm.js');
		craft()->templates->includeJs('new Craft.AccountSettingsForm('.($variables['account']->isCurrent() ? 'true' : 'false').');');

		craft()->templates->includeTranslations(
			'Please enter your current password.',
			'Please enter your password.'
		);

		$this->renderTemplate('users/_edit', $variables);
	}

	/**
	 * Registers a new user, or saves an existing user's account settings.
	 *
	 * @throws HttpException|Exception
	 * @return null
	 */
	public function actionSaveUser()
	{
		$this->requirePostRequest();

		$currentUser = craft()->userSession->getUser();
		$thisIsPublicRegistration = false;
		$requireEmailVerification = craft()->systemSettings->getSetting('users', 'requireEmailVerification');

		$userId = craft()->request->getPost('userId');
		$isNewUser = !$userId;

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
				// Don't worry about new password validation. That will be taken care of in the service.
				$user->newPassword = $newPassword;
			}

			if ($newEmail)
			{
				// Does that email need to be verified?
				if ($requireEmailVerification && (!craft()->userSession->isAdmin() || craft()->request->getPost('verificationRequired')))
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
					$requireEmailVerification = false;
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
			if ($requireEmailVerification)
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
		if (craft()->users->saveUser($user))
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

			// Is this public registration, and is the user going to be activated automatically?
			if ($thisIsPublicRegistration && $user->status == UserStatus::Active)
			{
				// Do we need to auto-login?
				if (craft()->config->get('autoLoginAfterAccountActivation') === true)
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
	 * @deprecated Deprecated in 2.0. Use {@link UsersController::saveUser()} instead.
	 * @return null
	 */
	public function actionSaveProfile()
	{
		craft()->deprecator->log('UsersController::saveProfile()', 'The users/saveProfile action has been deprecated. Use users/saveUser instead.');
		$this->actionSaveUser();
	}

	/**
	 * Upload a user photo.
	 *
	 * @return null
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
				$userName = AssetsHelper::cleanAssetName($user->username);

				$folderPath = craft()->path->getTempUploadsPath().'userphotos/'.$userName.'/';

				IOHelper::clearFolder($folderPath);

				IOHelper::ensureFolderExists($folderPath);
				$fileName = AssetsHelper::cleanAssetName($file['name']);

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
			Craft::log('There was an error uploading the photo: '.$exception->getMessage(), LogLevel::Error);
		}

		$this->returnErrorJson(Craft::t('There was an error uploading your photo.'));
	}

	/**
	 * Crop user photo.
	 *
	 * @return null
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
			$userName = AssetsHelper::cleanAssetName($user->username);

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
	 *
	 * @return null
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
	 *
	 * @return null
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
	 *
	 * @throws HttpException
	 * @return null
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
	 *
	 * @throws HttpException
	 * @return null
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
	 *
	 * @throws HttpException
	 * @return null
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
	 *
	 * @throws HttpException
	 * @return null
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

		// Even if you have administrateUsers permissions, only and admin should be able to un-suspend another admin.
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
	 *
	 * @return null
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

	// Private Methods
	// =========================================================================

	/**
	 * Redirects the user after a successful login attempt, or if they visited the Login page while they were already
	 * logged in.
	 *
	 * @param bool $setNotice Whether a flash notice should be set, if this isn't an Ajax request.
	 *
	 * @return null
	 */
	private function _handleSuccessfulLogin($setNotice)
	{
		// If this was an Ajax request, just return success:true
		if (craft()->request->isAjaxRequest())
		{
			$this->returnJson(array(
				'success' => true
			));
		}
		else
		{
			if ($setNotice)
			{
				craft()->userSession->setNotice(Craft::t('Logged in.'));
			}

			// Get the current user
			$currentUser = craft()->userSession->getUser();

			// Were they trying to access a URL beforehand?
			$defaultUrl = craft()->userSession->getReturnUrl();

			if ($defaultUrl === null || $defaultUrl == craft()->request->getPath())
			{
				// If this is a CP request and they can access the control panel, send them wherever
				// postCpLoginRedirect tells us
				if (craft()->request->isCpRequest() && $currentUser->can('accessCp'))
				{
					$postCpLoginRedirect = craft()->config->get('postCpLoginRedirect');
					$defaultUrl = UrlHelper::getCpUrl($postCpLoginRedirect);
				}
				else
				{
					// Otherwise send them wherever postLoginRedirect tells us
					$postLoginRedirect = craft()->config->get('postLoginRedirect');
					$defaultUrl = UrlHelper::getSiteUrl($postLoginRedirect);
				}
			}

			$this->redirectToPostedUrl($currentUser, $defaultUrl);
		}
	}

	/**
	 * @param $user
	 *
	 * @return null
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
	 * @param int $userId
	 *
	 * @throws Exception
	 * @return null
	 */
	private function _noUserExists($userId)
	{
		throw new Exception(Craft::t('No user exists with the ID “{id}”.', array('id' => $userId)));
	}

	/**
	 * @param $userId
	 *
	 * @return null
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
	 *
	 * @return null
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

	/**
	 * @param $user
	 * @param $currentUser
	 *
	 * @return null
	 */
	private function _processUserGroupsPermissions($user, $currentUser)
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
