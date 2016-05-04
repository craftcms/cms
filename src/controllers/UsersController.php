<?php
namespace Craft;

/**
 * The UsersController class is a controller that handles various user account related tasks such as logging-in,
 * impersonating a user, logging out, forgetting passwords, setting passwords, validating accounts, activating
 * accounts, creating users, saving users, processing user avatars, deleting, suspending and un-suspending users.
 *
 * Note that all actions in the controller, except {@link actionLogin}, {@link actionLogout}, {@link actionGetAuthTimeout},
 * {@link actionSendPasswordResetEmail}, {@link actionSetPassword}, {@link actionVerifyEmail} and {@link actionSaveUser} require an
 * authenticated Craft session via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
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
	protected $allowAnonymous = array('actionLogin', 'actionLogout', 'actionGetAuthTimeout', 'actionForgotPassword', 'actionSendPasswordResetEmail', 'actionSendActivationEmail', 'actionSaveUser', 'actionSetPassword', 'actionVerifyEmail');

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
			// First, a little house-cleaning for expired, pending users.
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
		$originalUserId = craft()->userSession->getId();

		craft()->httpSession->add(UserSessionService::USER_IMPERSONATE_KEY, $originalUserId);

		if (craft()->userSession->loginByUserId($userId))
		{
			craft()->userSession->setNotice(Craft::t('Logged in.'));
			$this->_handleSuccessfulLogin(true);
		}
		else
		{
			craft()->httpSession->remove(UserSessionService::USER_IMPERSONATE_KEY);
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
		$return = array('timeout' => craft()->userSession->getAuthTimeout());

		if (craft()->config->get('enableCsrfProtection'))
		{
			$return['csrfTokenValue'] = craft()->request->getCsrfToken();
		}

		$this->returnJson($return);
	}

	/**
	 * Returns how many seconds are left in the current elevated user session.
	 *
	 * @return null
	 */
	public function actionGetElevatedSessionTimeout()
	{
		$return = array(
			'timeout' => craft()->userSession->getElevatedSessionTimeout()
		);

		$this->returnJson($return);
	}

	/**
	 * Starts an elevated user session.
	 *
	 * @return null
	 */
	public function actionStartElevatedSession()
	{
		$password = craft()->request->getPost('password');
		$success = craft()->userSession->startElevatedSession($password);

		$this->returnJson(array(
			'success' => $success
		));
	}

	/**
	 * @return null
	 */
	public function actionLogout()
	{
		craft()->userSession->logout(false);

		if (craft()->config->get('enableCsrfProtection'))
		{
			// Manually nuke the CSRF cookie (if there is one).
			craft()->request->deleteCookie(craft()->request->csrfTokenName);

			// Generate a new one.
			craft()->request->getCsrfToken();
		}

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
	 * Sends a password reset email.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionSendPasswordResetEmail()
	{
		$this->requirePostRequest();

		$errors = array();

		// If someone's logged in and they're allowed to edit other users, then see if a userId was submitted
		if (craft()->userSession->checkPermission('editUsers'))
		{
			$userId = craft()->request->getPost('userId');

			if ($userId)
			{
				$user = craft()->users->getUserById($userId);

				if (!$user)
				{
					throw new HttpException(404);
				}
			}
		}

		if (!isset($user))
		{
			$loginName = craft()->request->getPost('loginName');

			if (!$loginName)
			{
				$errors[] = Craft::t('Username or email is required.');
			}
			else
			{
				$user = craft()->users->getUserByUsernameOrEmail($loginName);

				if (!$user)
				{
					$errors[] = Craft::t('Invalid username or email.');
				}
			}
		}

		if (!empty($user))
		{
			if (craft()->users->sendPasswordResetEmail($user))
			{
				if (craft()->request->isAjaxRequest())
				{
					$this->returnJson(array('success' => true));
				}
				else
				{
					craft()->userSession->setNotice(Craft::t('Password reset email sent.'));
					$this->redirectToPostedUrl();
				}
			}

			$errors[] = Craft::t('There was a problem sending the password reset email.');
		}

		if (craft()->request->isAjaxRequest())
		{
			$this->returnErrorJson($errors);
		}
		else
		{
			// Send the data back to the template
			craft()->urlManager->setRouteVariables(array(
				'errors'    => $errors,
				'loginName' => isset($loginName) ? $loginName : null,
			));
		}
	}

	/**
	 * Generates a new verification code for a given user, and returns its URL.
	 *
	 * @throws HttpException|Exception
	 * @return null
	 */
	public function actionGetPasswordResetUrl()
	{
		$this->requireAdmin();

		if (!$this->_verifyElevatedSession())
		{
			throw new HttpException(403);
		}

		$userId = craft()->request->getRequiredParam('userId');
		$user = craft()->users->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		echo craft()->users->getPasswordResetUrl($user);
		craft()->end();
	}

	/**
	 * Sets a user's password once they've verified they have access to their email.
	 *
	 * @throws HttpException|Exception
	 * @return null
	 */
	public function actionSetPassword()
	{
		// Have they just submitted a password, or are we just displaying the page?
		if (!craft()->request->isPostRequest())
		{
			if ($info = $this->_processTokenRequest())
			{
				$userToProcess = $info['userToProcess'];
				$id = $info['id'];
				$code = $info['code'];

				craft()->userSession->processUsernameCookie($userToProcess->username);

				// Send them to the set password template.
				$this->_renderSetPasswordTemplate($userToProcess, array(
					'code'    => $code,
					'id'      => $id,
					'newUser' => ($userToProcess->password ? false : true),
				));
			}
		}
		else
		{
			// POST request. They've just set the password.
			$code          = craft()->request->getRequiredPost('code');
			$id            = craft()->request->getRequiredParam('id');
			$userToProcess = craft()->users->getUserByUid($id);

			// See if we still have a valid token.
			$isCodeValid = craft()->users->isVerificationCodeValidForUser($userToProcess, $code);

			if (!$userToProcess || !$isCodeValid)
			{
				$this->_processInvalidToken($userToProcess);
			}

			$newPassword = craft()->request->getRequiredPost('newPassword');
			$userToProcess->newPassword = $newPassword;

			if ($userToProcess->passwordResetRequired)
			{
				$forceDifferentPassword = true;
			}
			else
			{
				$forceDifferentPassword = false;
			}

			if (craft()->users->changePassword($userToProcess, $forceDifferentPassword))
			{
				if ($userToProcess->status == UserStatus::Pending)
				{
					// Activate them
					craft()->users->activateUser($userToProcess);

					// Treat this as an activation request
					$this->_onAfterActivateUser($userToProcess);
				}

				// Can they access the CP?
				if ($userToProcess->can('accessCp'))
				{
					// Send them to the CP login page
					$url = UrlHelper::getCpUrl(craft()->config->getCpLoginPath());
				}
				else
				{
					// Send them to the 'setPasswordSuccessPath'.
					$setPasswordSuccessPath = craft()->config->getLocalized('setPasswordSuccessPath');
					$url = UrlHelper::getSiteUrl($setPasswordSuccessPath);
				}

				$this->redirect($url);
			}

			craft()->userSession->setNotice(Craft::t('Couldn’t update password.'));

			$errors = $userToProcess->getErrors('newPassword');

			$this->_renderSetPasswordTemplate($userToProcess, array(
				'errors' => $errors,
				'code' => $code,
				'id' => $id,
				'newUser' => ($userToProcess->password ? false : true),
			));
		}
	}

	/**
	 * Verifies that a user has access to an email address.
	 *
	 * @deprecated Deprecated in 2.3. Use {@link UsersController::actionVerifyEmail()} instead.
	 * @return null
	 */
	public function actionValidate()
	{
		craft()->deprecator->log('UsersController::validate()', 'The users/validate action has been deprecated. Use users/verifyEmail instead.');
		$this->actionVerifyEmail();
	}

	/**
	 * Verifies that a user has access to an email address.
	 *
	 * @return null
	 */
	public function actionVerifyEmail()
	{
		if ($info = $this->_processTokenRequest())
		{
			$userToProcess = $info['userToProcess'];
			$userIsPending = $userToProcess->status == UserStatus::Pending;

			craft()->users->verifyEmailForUser($userToProcess);

			if ($userIsPending)
			{
				// They were just activated, so treat this as an activation request
				$this->_onAfterActivateUser($userToProcess);
			}

			// Redirect to the site/CP root
			$url = UrlHelper::getUrl('');
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
		// Determine which user account we're editing
		// ---------------------------------------------------------------------

		$craftEdition = craft()->getEdition();
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
			else if ($craftEdition == Craft::Pro)
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
		else
		{
			if ($account == 'client')
			{
				$isClientAccount = true;
			}
		}

		$variables['isNewAccount'] = !$variables['account']->id;

		// Make sure they have permission to edit this user
		// ---------------------------------------------------------------------

		if (!$variables['account']->isCurrent())
		{
			if ($variables['isNewAccount'])
			{
				craft()->userSession->requirePermission('registerUsers');
			}
			else
			{
				craft()->userSession->requirePermission('editUsers');
			}
		}

		// Determine which actions should be available
		// ---------------------------------------------------------------------

		$statusActions = array();
		$loginActions = array();
		$sketchyActions = array();

		if ($craftEdition >= Craft::Client && !$variables['isNewAccount'])
		{
			switch ($variables['account']->getStatus())
			{
				case UserStatus::Pending:
				{
					$variables['statusLabel'] = Craft::t('Unverified');

					$statusActions[] = array('action' => 'users/sendActivationEmail', 'label' => Craft::t('Send activation email'));

					if (craft()->userSession->isAdmin())
					{
						$statusActions[] = array('id' => 'copy-passwordreset-url', 'label' => Craft::t('Copy activation URL'));
						$statusActions[] = array('action' => 'users/activateUser', 'label' => Craft::t('Activate account'));
					}

					break;
				}
				case UserStatus::Locked:
				{
					$variables['statusLabel'] = Craft::t('Locked');

					if (craft()->userSession->checkPermission('administrateUsers'))
					{
						$statusActions[] = array('action' => 'users/unlockUser', 'label' => Craft::t('Unlock'));
					}

					break;
				}
				case UserStatus::Suspended:
				{
					$variables['statusLabel'] = Craft::t('Suspended');

					if (craft()->userSession->checkPermission('administrateUsers'))
					{
						$statusActions[] = array('action' => 'users/unsuspendUser', 'label' => Craft::t('Unsuspend'));
					}

					break;
				}
				case UserStatus::Active:
				{
					$variables['statusLabel'] = Craft::t('Active');

					if (!$variables['account']->isCurrent())
					{
						$statusActions[] = array('action' => 'users/sendPasswordResetEmail', 'label' => Craft::t('Send password reset email'));

						if (craft()->userSession->isAdmin())
						{
							$statusActions[] = array('id' => 'copy-passwordreset-url', 'label' => Craft::t('Copy password reset URL'));
						}
					}

					break;
				}
			}

			if (!$variables['account']->isCurrent())
			{
				if (craft()->userSession->isAdmin())
				{
					$loginActions[] = array('action' => 'users/impersonate', 'label' => Craft::t('Login as {user}', array('user' => $variables['account']->getName())));
				}

				if (craft()->userSession->checkPermission('administrateUsers') && $variables['account']->getStatus() != UserStatus::Suspended)
				{
					$sketchyActions[] = array('action' => 'users/suspendUser', 'label' => Craft::t('Suspend'));
				}

				if (craft()->userSession->checkPermission('deleteUsers'))
				{
					$sketchyActions[] = array('id' => 'delete-btn', 'label' => Craft::t('Delete…'));
				}
			}
		}

		$variables['actions'] = array();

		if ($statusActions)
		{
			array_push($variables['actions'], $statusActions);
		}

		// Give plugins a chance to add more actions
		$pluginActions = craft()->plugins->call('addUserAdministrationOptions', array($variables['account']), true);

		if ($pluginActions)
		{
			$variables['actions'] = array_merge($variables['actions'], array_values($pluginActions));
		}

		if ($loginActions)
		{
			array_push($variables['actions'], $loginActions);
		}

		if ($sketchyActions)
		{
			array_push($variables['actions'], $sketchyActions);
		}

		// Set the appropriate page title
		// ---------------------------------------------------------------------

		if (!$variables['isNewAccount'])
		{
			if ($variables['account']->isCurrent())
			{
				$variables['title'] = Craft::t('My Account');
			}
			else
			{
				$variables['title'] = Craft::t("{user}’s Account", array('user' => $variables['account']->name));
			}
		}
		else if ($isClientAccount)
		{
			$variables['title'] = Craft::t('Register the client’s account');
		}
		else
		{
			$variables['title'] = Craft::t("Register a new user");
		}

		// ---------------------------------------------------------------------
		$variables['selectedTab'] = 'account';

		$variables['tabs'] = array(
				'account' => array(
						'label' => Craft::t('Account'),
						'url'   => '#account',
				)
		);

		// No need to show the Profile tab if it's a new user (can't have an avatar yet) and there's no user fields.
		if (!$variables['isNewAccount'] || ($craftEdition == Craft::Pro && $variables['account']->getFieldLayout()->getFields()))
		{
			$variables['tabs']['profile'] = array(
					'label' => Craft::t('Profile'),
					'url'   => '#profile',
			);
		}



		// Show the permission tab for the users that can change them on Craft Client+ editions (unless
		// you're on Client and you're the admin account. No need to show since we always need an admin on Client)
		if (
			($craftEdition == Craft::Pro && craft()->userSession->getUser()->can('assignUserPermissions')) ||
			($craftEdition == Craft::Client && $isClientAccount && craft()->userSession->isAdmin())
		)
		{
			$variables['tabs']['perms'] = array(
					'label' => Craft::t('Permissions'),
					'url'   => '#perms',
			);
		}

		// Just one tab looks awkward, so just don't show them at all then.
		if (count($variables['tabs']) == 1)
		{
			$variables['tabs'] = array();
		}
		else
		{
			if ($variables['account']->hasErrors())
			{
				// Add the 'error' class to any tabs that have errors
				$errors = $variables['account']->getErrors();
				$accountFields = array('username', 'firstName', 'lastName', 'email', 'password', 'newPassword', 'currentPassword', 'passwordResetRequired', 'preferredLocale');

				foreach ($errors as $attribute => $error)
				{
					if (isset($variables['tabs']['account']) && in_array($attribute, $accountFields))
					{
						$variables['tabs']['account']['class'] = 'error';
					}
					else if (isset($variables['tabs']['profile']))
					{
						$variables['tabs']['profile']['class'] = 'error';
					}
				}
			}
		}

		// Load the resources and render the page
		// ---------------------------------------------------------------------

		craft()->templates->includeCssResource('css/account.css');
		craft()->templates->includeJsResource('js/AccountSettingsForm.js');
		craft()->templates->includeJs('new Craft.AccountSettingsForm('.JsonHelper::encode($variables['account']->id).', '.($variables['account']->isCurrent() ? 'true' : 'false').');');

		craft()->templates->includeTranslations(
			'Please enter your current password.',
			'Please enter your password.'
		);

		$this->renderTemplate('users/_edit', $variables);
	}

	/**
	 * Provides an endpoint for saving a user account.
	 *
	 * This action accounts for the following scenarios:
	 *
	 * - An admin registering a new user account.
	 * - An admin editing an existing user account.
	 * - A normal user with user-administration permissions registering a new user account.
	 * - A normal user with user-administration permissions editing an existing user account.
	 * - A guest registering a new user account ("public registration").
	 *
	 * This action behaves the same regardless of whether it was requested from the Control Panel or the front-end site.
	 *
	 * @throws HttpException|Exception
	 * @return null
	 */
	public function actionSaveUser()
	{
		$this->requirePostRequest();

		$craftEdition = craft()->getEdition();
		$currentUser = craft()->userSession->getUser();
		$requireEmailVerification = craft()->systemSettings->getSetting('users', 'requireEmailVerification');

		// Get the user being edited
		// ---------------------------------------------------------------------

		$userId = craft()->request->getPost('userId');
		$isNewUser = !$userId;
		$thisIsPublicRegistration = false;

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
		else if ($craftEdition == Craft::Client)
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

		$isCurrentUser = $user->isCurrent();

		if ($isCurrentUser)
		{
			// Remember the old username in case it changes
			$oldUsername = $user->username;
		}

		// Handle secure properties (email and password)
		// ---------------------------------------------------------------------

		$verifyNewEmail = false;

		// Are they allowed to set the email address?
		if ($isNewUser || $isCurrentUser || $currentUser->can('changeUserEmails'))
		{
			$newEmail = craft()->request->getPost('email');

			// Did it just change?
			if ($newEmail && $newEmail == $user->email)
			{
				$newEmail = null;
			}

			if ($newEmail)
			{
				// Does that email need to be verified?
				if ($requireEmailVerification && (!$currentUser || !$currentUser->admin || craft()->request->getPost('sendVerificationEmail')))
				{
					// Save it as an unverified email for now
					$user->unverifiedEmail = $newEmail;
					$verifyNewEmail = true;

					// If this is a new user, set it as their main email address too
					if ($isNewUser)
					{
						$user->email = $newEmail;
					}
				}
				else
				{
					// We trust them
					$user->email = $newEmail;
				}
			}
		}

		// Are they allowed to set a new password?
		if ($thisIsPublicRegistration)
		{
			$user->newPassword = craft()->request->getPost('password', '');
		}
		else if ($isCurrentUser)
		{
			// If there was a newPassword input but it was empty, pretend it didn't exist
			$user->newPassword = (craft()->request->getPost('newPassword') ?: null);
		}

		// If editing an existing user and either of these properties are being changed,
		// require the user's current password for additional security
		if (!$isNewUser && (!empty($newEmail) || $user->newPassword))
		{
			if (!$this->_verifyElevatedSession())
			{
				Craft::log('Tried to change the email or password for userId: '.$user->id.', but the current password does not match what the user supplied.', LogLevel::Warning);
				$user->addError('currentPassword', Craft::t('Incorrect current password.'));
			}
		}

		// Handle the rest of the user properties
		// ---------------------------------------------------------------------

		// Is the site set to use email addresses as usernames?
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
		$user->weekStartDay    = craft()->request->getPost('weekStartDay', $user->weekStartDay);

		// If email verification is required, then new users will be saved in a pending state,
		// even if an admin is doing this and opted to not send the verification email
		if ($isNewUser && $requireEmailVerification)
		{
			$user->pending = true;
		}

		// There are some things only admins can change
		if ($currentUser && $currentUser->admin)
		{
			$user->passwordResetRequired = (bool) craft()->request->getPost('passwordResetRequired', $user->passwordResetRequired);

			// Is their admin status changing?
			if (($adminParam = craft()->request->getPost('admin', $user->admin)) != $user->admin)
			{
				// Making someone an admin requires an elevated session
				if ($adminParam)
				{
					$this->requireElevatedSession();
				}

				$user->admin = $adminParam;
			}
		}

		// If this is Craft Pro, grab any profile content from post
		if ($craftEdition == Craft::Pro)
		{
			$user->setContentFromPost('fields');
		}

		// Validate and save!
		// ---------------------------------------------------------------------
		$imageValidates = true;
		$userPhoto = UploadedFile::getInstanceByName('userPhoto');

		if ($userPhoto && !ImageHelper::isImageManipulatable($userPhoto->getExtensionName()))
		{
			$imageValidates = false;
			$user->addError('userPhoto', Craft::t("The user photo provided is not an image."));
		}

		if ($imageValidates && craft()->users->saveUser($user))
		{
			// Is this the current user, and did their username just change?
			if ($isCurrentUser && $user->username !== $oldUsername)
			{
				// Update the username cookie
				craft()->userSession->processUsernameCookie($user->username);
			}

			// Save the user's photo, if it was submitted
			$this->_processUserPhoto($user);

			// If this is public registration, assign the user to the default user group
			if ($thisIsPublicRegistration)
			{
				// Assign them to the default user group
				craft()->userGroups->assignUserToDefaultGroup($user);
			}
			else
			{
				// Assign user groups and permissions if the current user is allowed to do that
				$this->_processUserGroupsPermissions($user);
			}

			// Do we need to send a verification email out?
			if ($verifyNewEmail)
			{
				// Temporarily set the unverified email on the UserModel so the verification email goes to the
				// right place
				$originalEmail = $user->email;
				$user->email = $user->unverifiedEmail;

				try
				{
					if ($isNewUser)
					{
						// Send the activation email
						craft()->users->sendActivationEmail($user);
					}
					else
					{
						// Send the standard verification email
						craft()->users->sendNewEmailVerifyEmail($user);
					}
				}
				catch (\phpmailerException $e)
				{
					craft()->userSession->setError(Craft::t('User saved, but couldn’t send verification email. Check your email settings.'));
				}

				// Put the original email back into place
				$user->email = $originalEmail;
			}

			if (isset($_POST['redirect']) && mb_strpos($_POST['redirect'], '{userId}') !== false)
			{
				craft()->deprecator->log('UsersController::saveUser():userId_redirect', 'The {userId} token within the ‘redirect’ param on users/saveUser requests has been deprecated. Use {id} instead.');
				$_POST['redirect'] = str_replace('{userId}', '{id}', $_POST['redirect']);
			}

			// Is this public registration, and was the user going to be activated automatically?
			$publicActivation = $thisIsPublicRegistration && $user->status == UserStatus::Active;

			if ($publicActivation)
			{
				// Maybe automatically log them in
				$this->_maybeLoginUserAfterAccountActivation($user);
			}

			if (craft()->request->isAjaxRequest())
			{
				$return['success']   = true;
				$return['id']        = $user->id;

				$this->returnJson($return);
			}
			else
			{
				craft()->userSession->setNotice(Craft::t('User saved.'));

				// Is this public registration, and is the user going to be activated automatically?
				if ($publicActivation)
				{
					$this->_redirectUserAfterAccountActivation($user);
				}
				else
				{
					$this->redirectToPostedUrl($user);
				}
			}
		}
		else
		{
			if (craft()->request->isAjaxRequest())
			{
				$this->returnJson(array(
					'errors' => $user->getErrors(),
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
		$file = UploadedFile::getInstanceByName('image-upload');

		try
		{
			// Make sure a file was uploaded
			if ($file)
			{
				$fileName = AssetsHelper::cleanAssetName($file->getName(), false, true);

				if (!ImageHelper::isImageManipulatable($file->getExtensionName()))
				{
					throw new Exception(Craft::t('The uploaded file is not an image.'));
				}

				$user = craft()->users->getUserById($userId);
				$userName = AssetsHelper::cleanAssetName($user->username, false, true);

				$folderPath = craft()->path->getTempUploadsPath().'userphotos/'.$userName.'/';

				IOHelper::clearFolder($folderPath);

				IOHelper::ensureFolderExists($folderPath);

				move_uploaded_file($file->getTempName(), $folderPath.$fileName);

				// Test if we will be able to perform image actions on this image
				if (!craft()->images->checkMemoryForImage($folderPath.$fileName))
				{
					IOHelper::deleteFile($folderPath.$fileName);
					$this->returnErrorJson(Craft::t('The uploaded image is too large'));
				}

				craft()->images->
					loadImage($folderPath.$fileName)->
					scaleToFit(500, 500, false)->
					saveAs($folderPath.$fileName);

				list ($width, $height) = ImageHelper::getImageSize($folderPath.$fileName);

				// If the file is in the format badscript.php.gif perhaps.
				if ($width && $height)
				{
					$html = craft()->templates->render('_components/tools/cropper_modal',
						array(
							'imageUrl' => UrlHelper::getResourceUrl('userphotos/temp/'.$userName.'/'.$fileName),
							'width' => $width,
							'height' => $height,
							'fileName' => $fileName
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
			$source = UrlHelper::stripQueryString($source);

			$user = craft()->users->getUserById($userId);
			$userName = AssetsHelper::cleanAssetName($user->username, false, true);

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

		// Only allow activation emails to be send to pending users.
		if ($user->getStatus() !== UserStatus::Pending)
		{
			throw new Exception(Craft::t('Invalid account status for user ID “{id}”.', array('id' => $userId)));
		}

		craft()->users->sendActivationEmail($user);

		if (craft()->request->isAjaxRequest())
		{
			$this->returnJson(array('success' => true));
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
	 * @throws Exception
	 * @throws HttpException
	 * @throws \CDbException
	 * @throws \Exception
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
		if ($user->admin)
		{
			craft()->userSession->requireAdmin();
		}

		// Are we transfering the user's content to a different user?
		$transferContentToId = craft()->request->getPost('transferContentTo');

		if (is_array($transferContentToId) && isset($transferContentToId[0]))
		{
			$transferContentToId = $transferContentToId[0];
		}

		if ($transferContentToId)
		{
			$transferContentTo = craft()->users->getUserById($transferContentToId);

			if (!$transferContentTo)
			{
				$this->_noUserExists($transferContentToId);
			}
		}
		else
		{
			$transferContentTo = null;
		}

		// Delete the user
		if (craft()->users->deleteUser($user, $transferContentTo))
		{
			craft()->userSession->setNotice(Craft::t('User deleted.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t delete the user.'));
		}
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
		$fieldLayout = craft()->fields->assembleLayoutFromPost();
		$fieldLayout->type = ElementType::User;
		craft()->fields->deleteLayoutsByType(ElementType::User);

		if (craft()->fields->saveLayout($fieldLayout))
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
		$this->requireAjaxRequest();

		if ($this->_verifyExistingPassword())
		{
			$this->returnJson(array('success' => true));
		}

		$this->returnErrorJson(Craft::t('Invalid password.'));
	}

	// Deprecated Methods
	// -------------------------------------------------------------------------

	/**
	 * Sends a Forgot Password email.
	 *
	 * @deprecated Deprecated in 2.3. Use {@link actionSendPasswordResetEmail()} instead.
	 * @return null
	 */
	public function actionForgotPassword()
	{
		// TODO: Log a deprecation error in Craft 3
		$this->actionSendPasswordResetEmail();
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
		// Get the current user
		$currentUser = craft()->userSession->getUser();

		// Were they trying to access a URL beforehand?
		$returnUrl = craft()->userSession->getReturnUrl(null, true);

		if ($returnUrl === null || $returnUrl == craft()->request->getPath())
		{
			// If this is a CP request and they can access the control panel, send them wherever
			// postCpLoginRedirect tells us
			if (craft()->request->isCpRequest() && $currentUser->can('accessCp'))
			{
				$postCpLoginRedirect = craft()->config->get('postCpLoginRedirect');
				$returnUrl = UrlHelper::getCpUrl($postCpLoginRedirect);
			}
			else
			{
				// Otherwise send them wherever postLoginRedirect tells us
				$postLoginRedirect = craft()->config->get('postLoginRedirect');
				$returnUrl = UrlHelper::getSiteUrl($postLoginRedirect);
			}
		}

		// If this was an Ajax request, just return success:true
		if (craft()->request->isAjaxRequest())
		{
			$this->returnJson(array(
				'success' => true,
				'returnUrl' => $returnUrl
			));
		}
		else
		{
			if ($setNotice)
			{
				craft()->userSession->setNotice(Craft::t('Logged in.'));
			}

			$this->redirectToPostedUrl($currentUser, $returnUrl);
		}
	}

	/**
	 * Renders the Set Password template for a given user.
	 *
	 * @param UserModel $user
	 * @param array     $variables
	 */
	private function _renderSetPasswordTemplate(UserModel $user, $variables)
	{
		// If the user doesn't have CP access, see if a custom Set Password template exists
		if (!$user->can('accessCp'))
		{
			craft()->templates->setTemplateMode(TemplateMode::Site);
			$templatePath = craft()->config->getLocalized('setPasswordPath');

			if (craft()->templates->doesTemplateExist($templatePath))
			{
				$this->renderTemplate($templatePath, $variables);
				return;
			}
		}

		// Otherwise go with the CP's template
		craft()->templates->setTemplateMode(TemplateMode::CP);
		$templatePath = craft()->config->getCpSetPasswordPath();
		$this->renderTemplate($templatePath, $variables);
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
	 * Verifies that the user has an elevated session, or that their current password was submitted with the request.
	 *
	 * @return bool
	 */
	private function _verifyElevatedSession()
	{
		return (craft()->userSession->hasElevatedSession() || $this->_verifyExistingPassword());
	}

	/**
	 * Verifies that the current user's password was submitted with the request.
	 *
	 * @return bool
	 */
	private function _verifyExistingPassword()
	{
		$currentUser = craft()->userSession->getUser();

		if (!$currentUser)
		{
			return false;
		}

		$currentHashedPassword = $currentUser->password;
		$currentPassword = craft()->request->getRequiredParam('password');

		return craft()->users->validatePassword($currentHashedPassword, $currentPassword);
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

			craft()->users->saveUserPhoto(AssetsHelper::cleanAssetName($userPhoto->getName()), $image, $user);

			IOHelper::deleteFile($userPhoto->getTempName());
		}
	}

	/**
	 * @param UserModel $user
	 *
	 * @return null
	 */
	private function _processUserGroupsPermissions(UserModel $user)
	{
		// Make sure there are assignUserPermissions
		if (craft()->userSession->checkPermission('assignUserPermissions'))
		{
			// Only Craft Pro has user groups
			if (craft()->getEdition() == Craft::Pro)
			{
				// Save any user groups
				$groupIds = craft()->request->getPost('groups');

				if ($groupIds !== null)
				{
					if (is_array($groupIds))
					{
						// See if there are any new groups in here
						$oldGroupIds = array();

						foreach ($user->getGroups() as $group)
						{
							$oldGroupIds[] = $group->id;
						}

						foreach ($groupIds as $groupId)
						{
							if (!in_array($groupId, $oldGroupIds))
							{
								// Yep. This will require an elevated session
								$this->requireElevatedSession();
								break;
							}
						}
					}

					craft()->userGroups->assignUserToGroups($user->id, $groupIds);
				}
			}

			// Craft Client+ has user permissions.
			if (craft()->getEdition() >= Craft::Client)
			{
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
					// See if there are any new permissions in here
					if (is_array($permissions))
					{
						foreach ($permissions as $permission)
						{
							if (!$user->can($permission))
							{
								// Yep. This will require an elevated session
								$this->requireElevatedSession();
								break;
							}
						}
					}

					craft()->userPermissions->saveUserPermissions($user->id, $permissions);
				}
			}
		}
	}

	/**
	 * @return array
	 * @throws HttpException
	 */
	private function _processTokenRequest()
	{
		if (craft()->userSession->isLoggedIn())
		{
			craft()->userSession->logout();
		}

		$id            = craft()->request->getRequiredParam('id');
		$userToProcess = craft()->users->getUserByUid($id);
		$code          = craft()->request->getRequiredParam('code');
		$isCodeValid   = false;

		if ($userToProcess)
		{
			// Fire an 'onBeforeVerifyUser' event
			craft()->users->onBeforeVerifyUser(new Event($this, array(
				'user' => $userToProcess
			)));

			$isCodeValid = craft()->users->isVerificationCodeValidForUser($userToProcess, $code);
		}

		if (!$userToProcess || !$isCodeValid)
		{
			$this->_processInvalidToken($userToProcess);
		}

		// Fire an 'onVerifyUser' event
		craft()->users->onVerifyUser(new Event($this, array(
			'user' => $userToProcess
		)));

		return array('code' => $code, 'id' => $id, 'userToProcess' => $userToProcess);
	}

	/**
	 * @param UserModel $user
	 *
	 * @throws HttpException
	 */
	private function _processInvalidToken($user)
	{
		$url = craft()->config->getLocalized('invalidUserTokenPath');

		if ($url == '')
		{
			// Check the deprecated config setting.
			// TODO: Add a deprecation log message in 3.0.
			$url = craft()->config->getLocalized('activateAccountFailurePath');
		}

		if ($url != '')
		{
			$this->redirect(UrlHelper::getSiteUrl($url));
		}
		else
		{
			if ($user && $user->can('accessCp'))
			{
				$url = UrlHelper::getCpUrl(craft()->config->getLoginPath());
			}
			else
			{
				$url = UrlHelper::getSiteUrl(craft()->config->getLoginPath());
			}

			throw new HttpException('200', Craft::t('Invalid verification code. Please [login or reset your password]({loginUrl}).', array('loginUrl' => $url)));
		}
	}

	/**
	 * Takes over after a user has been activated.
	 *
	 * @param UserModel $user The user that was just activated
	 *
	 * @return void
	 */
	private function _onAfterActivateUser(UserModel $user)
	{
		$this->_maybeLoginUserAfterAccountActivation($user);

		if (!craft()->request->isAjaxRequest())
		{
			$this->_redirectUserAfterAccountActivation($user);
		}
	}

	/**
	 * Possibly log a user in right after they were activate, if Craft is configured to do so.
	 *
	 * @param UserModel $user The user that was just activated
	 *
	 * @return bool Whether the user was just logged in
	 */
	private function _maybeLoginUserAfterAccountActivation(UserModel $user)
	{
		if (craft()->config->get('autoLoginAfterAccountActivation') === true)
		{
			return craft()->userSession->loginByUserId($user->id, false, true);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Redirect the browser after a user’s account has been activated.
	 *
	 * @param UserModel $user The user that was just activated
	 *
	 * @return void
	 */
	private function _redirectUserAfterAccountActivation(UserModel $user)
	{
		// Can they access the CP?
		if ($user->can('accessCp'))
		{
			$postCpLoginRedirect = craft()->config->get('postCpLoginRedirect');
			$url = UrlHelper::getCpUrl($postCpLoginRedirect);
			$this->redirect($url);
		}
		else
		{
			$activateAccountSuccessPath = craft()->config->getLocalized('activateAccountSuccessPath');
			$url = UrlHelper::getSiteUrl($activateAccountSuccessPath);
			$this->redirectToPostedUrl($user, $url);
		}
	}
}
