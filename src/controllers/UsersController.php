<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\Exception;
use craft\app\errors\HttpException;
use craft\app\events\UserEvent;
use craft\app\helpers\AssetsHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\UrlHelper;
use craft\app\elements\User;
use craft\app\services\Users;
use craft\app\web\Controller;
use craft\app\web\Response;
use craft\app\web\UploadedFile;

/**
 * The UsersController class is a controller that handles various user account related tasks such as logging-in,
 * impersonating a user, logging out, forgetting passwords, setting passwords, validating accounts, activating
 * accounts, creating users, saving users, processing user avatars, deleting, suspending and un-suspending users.
 *
 * Note that all actions in the controller, except [[actionLogin]], [[actionLogout]], [[actionGetRemainingSessionTime]],
 * [[actionSendPasswordResetEmail]], [[actionSetPassword]], [[actionVerifyEmail]] and [[actionSaveUser]] require an
 * authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UsersController extends Controller
{
	// Properties
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected $allowAnonymous = ['login', 'logout', 'get-remaining-session-time', 'send-password-reset-email', 'send-activation-email', 'save-user', 'set-password', 'verify-email'];

	// Public Methods
	// =========================================================================

	/**
	 * Displays the login template, and handles login post requests.
	 *
	 * @return Response|null
	 */
	public function actionLogin()
	{
		if (!Craft::$app->getUser()->getIsGuest())
		{
			// Too easy.
			return $this->_handleSuccessfulLogin(false);
		}

		if (!Craft::$app->getRequest()->getIsPost())
		{
			return;
		}

		// First, a little house-cleaning for expired, pending users.
		Craft::$app->getUsers()->purgeExpiredPendingUsers();

		$loginName = Craft::$app->getRequest()->getBodyParam('loginName');
		$password = Craft::$app->getRequest()->getBodyParam('password');
		$rememberMe = (bool) Craft::$app->getRequest()->getBodyParam('rememberMe');

		// Does a user exist with that username/email?
		$user = Craft::$app->getUsers()->getUserByUsernameOrEmail($loginName);

		if (!$user)
		{
			return $this->_handleInvalidLogin(User::AUTH_USERNAME_INVALID);
		}

		// Did they submit a valid password, and is the user capable of being logged-in?
		if (!$user->authenticate($password))
		{
			return $this->_handleInvalidLogin($user->authError, $user);
		}

		// Log them in
		$duration = Craft::$app->getConfig()->getUserSessionDuration($rememberMe);

		if (Craft::$app->getUser()->login($user, $duration))
		{
			return $this->_handleSuccessfulLogin(true);
		}
		else
		{
			// Unknown error
			return $this->_handleInvalidLogin(null, $user);
		}
	}

	/**
	 * Logs a user in for impersonation.  Requires you to be an administrator.
	 *
	 * @return Response|null
	 */
	public function actionImpersonate()
	{
		$this->requireLogin();
		$this->requireAdmin();
		$this->requirePostRequest();

		$userId = Craft::$app->getRequest()->getBodyParam('userId');
		$originalUserId = Craft::$app->getUser()->getId();

		if (Craft::$app->getUser()->loginByUserId($userId))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'Logged in.'));
			Craft::$app->getSession()->set(User::IMPERSONATE_KEY, $originalUserId);

			return $this->_handleSuccessfulLogin(true);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'There was a problem impersonating this user.'));
			Craft::error(Craft::$app->getUser()->getIdentity()->username.' tried to impersonate userId: '.$userId.' but something went wrong.', __METHOD__);
		}
	}

	/**
	 * Returns how many seconds are left in the current user session.
	 *
	 * @return null
	 */
	public function actionGetRemainingSessionTime()
	{
		echo Craft::$app->getUser()->getRemainingSessionTime();
		Craft::$app->end();
	}

	/**
	 * @return null
	 */
	public function actionLogout()
	{
		Craft::$app->getUser()->logout();

		if (Craft::$app->getRequest()->getIsAjax())
		{
			return $this->asJson([
				'success' => true
			]);
		}
		else
		{
			return $this->redirect('');
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

		$errors = [];

		// If someone's logged in and they're allowed to edit other users, then see if a userId was submitted
		if (Craft::$app->getUser()->checkPermission('editUsers'))
		{
			$userId = Craft::$app->getRequest()->getBodyParam('userId');

			if ($userId)
			{
				$user = Craft::$app->getUsers()->getUserById($userId);

				if (!$user)
				{
					throw new HttpException(404);
				}
			}
		}

		if (!isset($user))
		{
			$loginName = Craft::$app->getRequest()->getBodyParam('loginName');

			if (!$loginName)
			{
				$errors[] = Craft::t('app', 'Username or email is required.');
			}
			else
			{
				$user = Craft::$app->getUsers()->getUserByUsernameOrEmail($loginName);

				if (!$user)
				{
					$errors[] = Craft::t('app', 'Invalid username or email.');
				}
			}
		}

		if (!empty($user))
		{
			if (Craft::$app->getUsers()->sendPasswordResetEmail($user))
			{
				if (Craft::$app->getRequest()->getIsAjax())
				{
					return $this->asJson(['success' => true]);
				}
				else
				{
					Craft::$app->getSession()->setNotice(Craft::t('app', 'Password reset email sent.'));
					return $this->redirectToPostedUrl();
				}
			}

			$errors[] = Craft::t('app', 'There was a problem sending the password reset email.');
		}

		if (Craft::$app->getRequest()->getIsAjax())
		{
			return $this->asErrorJson($errors);
		}
		else
		{
			// Send the data back to the template
			Craft::$app->getUrlManager()->setRouteParams([
				'errors'    => $errors,
				'loginName' => isset($loginName) ? $loginName : null,
			]);
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

		if (!$this->_verifyExistingPassword())
		{
			throw new HttpException(403);
		}

		$userId = Craft::$app->getRequest()->getRequiredParam('userId');
		$user = Craft::$app->getUsers()->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		echo Craft::$app->getUsers()->getPasswordResetUrl($user);
		Craft::$app->end();
	}

	/**
	 * Sets a user's password once they've verified they have access to their email.
	 *
	 * @return string The rendering result
	 * @throws HttpException|Exception
	 */
	public function actionSetPassword()
	{
		// Have they just submitted a password, or are we just displaying the page?
		if (!Craft::$app->getRequest()->getIsPost())
		{
			if ($info = $this->_processTokenRequest())
			{
				$userToProcess = $info['userToProcess'];
				$id = $info['id'];
				$code = $info['code'];

				Craft::$app->getUser()->sendUsernameCookie($userToProcess);

				// Send them to the set password template.
				return $this->_renderSetPasswordTemplate($userToProcess, [
					'code'    => $code,
					'id'      => $id,
					'newUser' => ($userToProcess->password ? false : true),
				]);
			}
		}
		else
		{
			// POST request. They've just set the password.
			$code          = Craft::$app->getRequest()->getRequiredBodyParam('code');
			$id            = Craft::$app->getRequest()->getRequiredParam('id');
			$userToProcess = Craft::$app->getUsers()->getUserByUid($id);

			// See if we still have a valid token.
			$isCodeValid = Craft::$app->getUsers()->isVerificationCodeValidForUser($userToProcess, $code);

			if (!$userToProcess || !$isCodeValid)
			{
				$this->_processInvalidToken($userToProcess);
			}

			$newPassword = Craft::$app->getRequest()->getRequiredBodyParam('newPassword');
			$userToProcess->newPassword = $newPassword;

			if ($userToProcess->passwordResetRequired)
			{
				$forceDifferentPassword = true;
			}
			else
			{
				$forceDifferentPassword = false;
			}

			if (Craft::$app->getUsers()->changePassword($userToProcess, $forceDifferentPassword))
			{
				if ($userToProcess->status == User::STATUS_PENDING)
				{
					// Activate them
					Craft::$app->getUsers()->activateUser($userToProcess);

					// Treat this as an activation request
					$this->_onAfterActivateUser($userToProcess);
				}

				// Can they access the CP?
				if ($userToProcess->can('accessCp'))
				{
					// Send them to the CP login page
					$url = UrlHelper::getCpUrl(Craft::$app->getConfig()->getCpLoginPath());
				}
				else
				{
					// Send them to the 'setPasswordSuccessPath'.
					$setPasswordSuccessPath = Craft::$app->getConfig()->getLocalized('setPasswordSuccessPath');
					$url = UrlHelper::getSiteUrl($setPasswordSuccessPath);
				}

				return $this->redirect($url);
			}

			Craft::$app->getSession()->setNotice(Craft::t('app', 'Couldn’t update password.'));

			$errors = $userToProcess->getErrors('newPassword');

			return $this->_renderSetPasswordTemplate($userToProcess, [
				'errors' => $errors,
				'code' => $code,
				'id' => $id,
				'newUser' => ($userToProcess->password ? false : true),
			]);
		}
	}

	/**
	 * Verifies that a user has access to an email address.
	 *
	 * @return null;
	 */
	public function actionVerifyEmail()
	{
		if ($info = $this->_processTokenRequest())
		{
			$userToProcess = $info['userToProcess'];
			$userIsPending = $userToProcess->status == User::STATUS_PENDING;

			Craft::$app->getUsers()->verifyEmailForUser($userToProcess);

			if ($userIsPending)
			{
				// They were just activated, so treat this as an activation request
				$this->_onAfterActivateUser($userToProcess);
			}

			// Redirect to the site/CP root
			$url = UrlHelper::getUrl('');
			return $this->redirect($url);
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

		$userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');
		$user = Craft::$app->getUsers()->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		if (Craft::$app->getUsers()->activateUser($user))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'Successfully activated the user.'));
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'There was a problem activating the user.'));
		}

		return $this->redirectToPostedUrl();
	}

	/**
	 * Edit a user account.
	 *
	 * @param int|string $userId The user’s ID, if any, or a string that indicates the user to be edited ('current' or 'client').
	 * @param User       $user   The user being edited, if there were any validation errors.
	 * @return string The rendering result
	 * @throws HttpException
	 */
	public function actionEditUser($userId = null, User $user = null)
	{
		// Determine which user account we're editing
		// ---------------------------------------------------------------------

		$isClientAccount = false;

		// This will be set if there was a validation error.
		if ($user === null)
		{
			// Are we editing a specific user account?
			if ($userId !== null)
			{
				switch ($userId)
				{
					case 'current':
					{
						$user = Craft::$app->getUser()->getIdentity();
						break;
					}
					case 'client':
					{
						$isClientAccount = true;
						$user = Craft::$app->getUsers()->getClient();

						if (!$user)
						{
							// Registering the Client
							$user = new User();
							$user->client = true;
						}

						break;
					}
					default:
					{
						$user = Craft::$app->getUsers()->getUserById($userId);

						if (!$user)
						{
							throw new HttpException(404);
						}
					}
				}
			}
			else if (Craft::$app->getEdition() == Craft::Pro)
			{
				// Registering a new user
				$user = new User();
			}
			else
			{
				// Nada.
				throw new HttpException(404);
			}
		}

		$isNewAccount = !$user->id;

		// Make sure they have permission to edit this user
		// ---------------------------------------------------------------------

		if (!$user->isCurrent())
		{
			if ($isNewAccount)
			{
				$this->requirePermission('registerUsers');
			}
			else
			{
				$this->requirePermission('editUsers');
			}
		}

		// Determine which actions should be available
		// ---------------------------------------------------------------------

		$statusActions  = [];
		$sketchyActions = [];

		if (Craft::$app->getEdition() >= Craft::Client && !$isNewAccount)
		{
			switch ($user->getStatus())
			{
				case User::STATUS_PENDING:
				{
					$statusLabel = Craft::t('app', 'Unverified');

					$statusActions[] = ['action' => 'users/send-activation-email', 'label' => Craft::t('app', 'Send activation email')];

					if (Craft::$app->getUser()->getIsAdmin())
					{
						$statusActions[] = ['id' => 'copy-passwordreset-url', 'label' => Craft::t('app', 'Copy activation URL')];
						$statusActions[] = ['action' => 'users/activate-user', 'label' => Craft::t('app', 'Activate account')];
					}

					break;
				}
				case User::STATUS_LOCKED:
				{
					$statusLabel = Craft::t('app', 'Locked');

					if (Craft::$app->getUser()->checkPermission('administrateUsers'))
					{
						$statusActions[] = ['action' => 'users/unlock-user', 'label' => Craft::t('app', 'Unlock')];
					}

					break;
				}
				case User::STATUS_SUSPENDED:
				{
					$statusLabel = Craft::t('app', 'Suspended');

					if (Craft::$app->getUser()->checkPermission('administrateUsers'))
					{
						$statusActions[] = ['action' => 'users/unsuspend-user', 'label' => Craft::t('app', 'Unsuspend')];
					}

					break;
				}
				case User::STATUS_ACTIVE:
				{
					$statusLabel = Craft::t('app', 'Active');

					if (!$user->isCurrent())
					{
						$statusActions[] = ['action' => 'users/send-password-reset-email', 'label' => Craft::t('app', 'Send password reset email')];

						if (Craft::$app->getUser()->getIsAdmin())
						{
							$statusActions[] = ['id' => 'copy-passwordreset-url', 'label' => Craft::t('app', 'Copy password reset URL')];
						}
					}

					break;
				}
			}

			if (!$user->isCurrent())
			{
				if (Craft::$app->getUser()->checkPermission('administrateUsers') && $user->getStatus() != User::STATUS_SUSPENDED)
				{
					$sketchyActions[] = ['action' => 'users/suspend-user', 'label' => Craft::t('app', 'Suspend')];
				}

				if (Craft::$app->getUser()->checkPermission('deleteUsers'))
				{
					$sketchyActions[] = ['id' => 'delete-btn', 'label' => Craft::t('app', 'Delete…')];
				}
			}
		}

		$actions = [];

		if ($statusActions)
		{
			array_push($actions, $statusActions);
		}

		// Give plugins a chance to add more actions
		$pluginActions = Craft::$app->getPlugins()->call('addUserAdministrationOptions', [$user], true);

		if ($pluginActions)
		{
			$actions = array_merge($actions, array_values($pluginActions));
		}

		if ($sketchyActions)
		{
			array_push($actions, $sketchyActions);
		}

		// Set the appropriate page title
		// ---------------------------------------------------------------------

		if (!$isNewAccount)
		{
			if ($user->isCurrent())
			{
				$title = Craft::t('app', 'My Account');
			}
			else
			{
				$title = Craft::t('app', '{user}’s Account', ['user' => $user->name]);
			}
		}
		else if ($isClientAccount)
		{
			$title = Craft::t('app', 'Register the client’s account');
		}
		else
		{
			$title = Craft::t('app', 'Register a new user');
		}

		// Show tabs if they have Craft Pro
		// ---------------------------------------------------------------------

		if (Craft::$app->getEdition() == Craft::Pro)
		{
			$tabs = [
				'account' => [
					'label' => Craft::t('app', 'Account'),
					'url'   => '#account',
				]
			];

			// No need to show the Profile tab if it's a new user (can't have an avatar yet) and there's no user fields.
			if (!$isNewAccount || $user->getFieldLayout()->getFields())
			{
				$tabs['profile'] = [
					'label' => Craft::t('app', 'Profile'),
					'url'   => '#profile',
				];
			}

			// If they can assign user groups and permissions, show the Permissions tab
			if (Craft::$app->getUser()->getIdentity()->can('assignUserPermissions'))
			{
				$tabs['perms'] = [
					'label' => Craft::t('app', 'Permissions'),
					'url'   => '#perms',
				];
			}

			$selectedTab = 'account';
		}
		else
		{
			$tabs = [];
			$selectedTab = null;
		}

		// Ugly.  But Users don't have a real fieldlayout/tabs.
		$accountFields = ['username', 'firstName', 'lastName', 'email', 'password', 'newPassword', 'currentPassword', 'passwordResetRequired'];

		if (Craft::$app->getEdition() == Craft::Pro && $user->hasErrors())
		{
			$errors = $user->getErrors();

			foreach ($errors as $attribute => $error)
			{
				if (in_array($attribute, $accountFields))
				{
					$tabs['account']['class'] = 'error';
				}
				else if (isset($tabs['profile']))
				{
					$tabs['profile']['class'] = 'error';
				}
			}
		}

		// Load the resources and render the page
		// ---------------------------------------------------------------------

		Craft::$app->getView()->registerCssResource('css/account.css');
		Craft::$app->getView()->registerJsResource('js/AccountSettingsForm.js');
		Craft::$app->getView()->registerJs('new Craft.AccountSettingsForm('.JsonHelper::encode($user->id).', '.($user->isCurrent() ? 'true' : 'false').');');

		Craft::$app->getView()->includeTranslations(
			'Please enter your current password.',
			'Please enter your password.'
		);

		return $this->renderTemplate('users/_edit', [
			'account' => $user,
			'isNewAccount' => $isNewAccount,
			'statusLabel' => (isset($statusLabel) ? $statusLabel : null),
			'actions' => $actions,
			'title' => $title,
			'tabs' => $tabs,
			'selectedTab' => $selectedTab
		]);
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

		$userComponent = Craft::$app->getUser();
		$currentUser = $userComponent->getIdentity();
		$requireEmailVerification = Craft::$app->getSystemSettings()->getSetting('users', 'requireEmailVerification');

		// Get the user being edited
		// ---------------------------------------------------------------------

		$userId = Craft::$app->getRequest()->getBodyParam('userId');
		$isNewUser = !$userId;
		$thisIsPublicRegistration = false;

		// Are we editing an existing user?
		if ($userId)
		{
			$user = User::find()
				->id($userId)
				->status(null)
				->withPassword()
				->one();

			if (!$user)
			{
				throw new Exception(Craft::t('app', 'No user exists with the ID “{id}”.', ['id' => $userId]));
			}

			if (!$user->isCurrent())
			{
				// Make sure they have permission to edit other users
				$this->requirePermission('editUsers');
			}
		}
		else if (Craft::$app->getEdition() == Craft::Client)
		{
			// Make sure they're logged in
			$this->requireAdmin();

			// Make sure there's no Client user yet
			if (Craft::$app->getUsers()->getClient())
			{
				throw new Exception(Craft::t('app', 'A client account already exists.'));
			}

			$user = new User();
			$user->client = true;
		}
		else
		{
			// Make sure this is Craft Pro, since that's required for having multiple user accounts
			Craft::$app->requireEdition(Craft::Pro);

			// Is someone logged in?
			if ($currentUser)
			{
				// Make sure they have permission to register users
				$this->requirePermission('registerUsers');
			}
			else
			{
				// Make sure public registration is allowed
				if (!Craft::$app->getSystemSettings()->getSetting('users', 'allowPublicRegistration'))
				{
					throw new HttpException(403);
				}

				$thisIsPublicRegistration = true;
			}

			$user = new User();
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
			$newEmail = Craft::$app->getRequest()->getBodyParam('email');

			// Make sure it actually changed
			if ($newEmail && $newEmail == $user->email)
			{
				$newEmail = null;
			}

			if ($newEmail)
			{
				// Does that email need to be verified?
				if ($requireEmailVerification && (!$currentUser || !$currentUser->admin || Craft::$app->getRequest()->getBodyParam('sendVerificationEmail')))
				{
					// Save it as an unverified email for now
					$user->unverifiedEmail = $newEmail;
					$verifyNewEmail = true;

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
			$user->newPassword = Craft::$app->getRequest()->getBodyParam('password');
		}
		else if ($isCurrentUser)
		{
			// If the password input was empty, pretend it didn't exist
			$user->newPassword = Craft::$app->getRequest()->getBodyParam('newPassword') ?: null;
		}

		// If editing an existing user and either of these properties are being changed,
		// require the user's current password for additional security
		if (!$isNewUser && (!empty($newEmail) || $user->newPassword !== null))
		{
			if (!$this->_verifyExistingPassword())
			{
				Craft::warning('Tried to change the email or password for userId: '.$user->id.', but the current password does not match what the user supplied.', __METHOD__);
				$user->addError('currentPassword', Craft::t('app', 'Incorrect current password.'));
			}
		}

		// Handle the rest of the user properties
		// ---------------------------------------------------------------------

		// Is the site set to use email addresses as usernames?
		if (Craft::$app->getConfig()->get('useEmailAsUsername'))
		{
			$user->username    =  $user->email;
		}
		else
		{
			$user->username    = Craft::$app->getRequest()->getBodyParam('username', ($user->username ? $user->username : $user->email));
		}

		$user->firstName       = Craft::$app->getRequest()->getBodyParam('firstName', $user->firstName);
		$user->lastName        = Craft::$app->getRequest()->getBodyParam('lastName', $user->lastName);

		// If email verification is required, then new users will be saved in a pending state,
		// even if an admin is doing this and opted to not send the verification email
		if ($isNewUser && $requireEmailVerification)
		{
			$user->pending = true;
		}

		// There are some things only admins can change
		if ($currentUser && $currentUser->admin)
		{
			$user->passwordResetRequired = (bool) Craft::$app->getRequest()->getBodyParam('passwordResetRequired', $user->passwordResetRequired);
			$user->admin                 = (bool) Craft::$app->getRequest()->getBodyParam('admin', $user->admin);
		}

		// If this is Craft Pro, grab any profile content from post
		if (Craft::$app->getEdition() == Craft::Pro)
		{
			$user->setContentFromPost('fields');
		}

		// Validate and save!
		// ---------------------------------------------------------------------

		if (Craft::$app->getUsers()->saveUser($user))
		{
			// Save their preferences too
			$preferences = [
				'locale'       => Craft::$app->getRequest()->getBodyParam('preferredLocale', $user->getPreference('locale')),
				'weekStartDay' => Craft::$app->getRequest()->getBodyParam('weekStartDay', $user->getPreference('weekStartDay')),
			];

			if ($user->admin)
			{
				$preferences = array_merge($preferences, [
					'enableDebugToolbarForSite' => (bool) Craft::$app->getRequest()->getBodyParam('enableDebugToolbarForSite', $user->getPreference('enableDebugToolbarForSite')),
					'enableDebugToolbarForCp'   => (bool) Craft::$app->getRequest()->getBodyParam('enableDebugToolbarForCp', $user->getPreference('enableDebugToolbarForCp')),
				]);
			}

			Craft::$app->getUsers()->saveUserPreferences($user, $preferences);

			// Is this the current user?
			if ($user->isCurrent())
			{
				// Make sure these preferences make it to the main identity user
				if ($user !== $currentUser)
				{
					$currentUser->mergePreferences($preferences);
				}

				$userComponent->saveDebugPreferencesToSession();
			}

			// Is this the current user, and did their username just change?
			if ($isCurrentUser && $user->username !== $oldUsername)
			{
				// Update the username cookie
				Craft::$app->getUser()->sendUsernameCookie($user);
			}

			// Save the user's photo, if it was submitted
			$this->_processUserPhoto($user);

			// If this is public registration, assign the user to the default user group
			if ($thisIsPublicRegistration)
			{
				// Assign them to the default user group
				Craft::$app->getUserGroups()->assignUserToDefaultGroup($user);
			}
			else
			{
				// Assign user groups and permissions if the current user is allowed to do that
				$this->_processUserGroupsPermissions($user);
			}

			// Do we need to send a verification email out?
			if ($verifyNewEmail)
			{
				// Temporarily set the unverified email on the User so the verification email goes to the
				// right place
				$originalEmail = $user->email;
				$user->email = $user->unverifiedEmail;

				try
				{
					if ($isNewUser)
					{
						// Send the activation email
						Craft::$app->getUsers()->sendActivationEmail($user);
					}
					else
					{
						// Send the standard verification email
						Craft::$app->getUsers()->sendNewEmailVerifyEmail($user);
					}
				}
				catch (\phpmailerException $e)
				{
					Craft::$app->getSession()->setError(Craft::t('app', 'User saved, but couldn’t send verification email. Check your email settings.'));
				}

				// Put the original email back into place
				$user->email = $originalEmail;
			}

			Craft::$app->getSession()->setNotice(Craft::t('app', 'User saved.'));

			// Is this public registration, and is the user going to be activated automatically?
			if ($thisIsPublicRegistration && $user->status == User::STATUS_ACTIVE)
			{
				// Do we need to auto-login?
				if (Craft::$app->getConfig()->get('autoLoginAfterAccountActivation') === true)
				{
					Craft::$app->getUser()->loginByUserId($user->id, false, true);
				}
			}

			return $this->redirectToPostedUrl($user);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save user.'));
		}

		// Send the account back to the template
		Craft::$app->getUrlManager()->setRouteParams([
			'user' => $user
		]);
	}

	/**
	 * Upload a user photo.
	 *
	 * @return null
	 */
	public function actionUploadUserPhoto()
	{
		$this->requireAjaxRequest();
		$this->requireLogin();
		$userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');

		if ($userId != Craft::$app->getUser()->getIdentity()->id)
		{
			$this->requirePermission('editUsers');
		}

		// Upload the file and drop it in the temporary folder
		$file = $_FILES['image-upload'];

		try
		{
			// Make sure a file was uploaded
			if (!empty($file['name']) && !empty($file['size'])  )
			{
				$user = Craft::$app->getUsers()->getUserById($userId);
				$userName = AssetsHelper::cleanAssetName($user->username, false);

				$folderPath = Craft::$app->getPath()->getTempUploadsPath().'/userphotos/'.$userName;

				IOHelper::clearFolder($folderPath);

				IOHelper::ensureFolderExists($folderPath);
				$filename = AssetsHelper::cleanAssetName($file['name']);

				move_uploaded_file($file['tmp_name'], $folderPath.'/'.$filename);

				// Test if we will be able to perform image actions on this image
				if (!Craft::$app->getImages()->checkMemoryForImage($folderPath.'/'.$filename))
				{
					IOHelper::deleteFile($folderPath.'/'.$filename);
					return $this->asErrorJson(Craft::t('app', 'The uploaded image is too large'));
				}

				Craft::$app->getImages()->cleanImage($folderPath.'/'.$filename);

				$constraint = 500;
				list ($width, $height) = getimagesize($folderPath.'/'.$filename);

				// If the file is in the format badscript.php.gif perhaps.
				if ($width && $height)
				{
					// Never scale up the images, so make the scaling factor always <= 1
					$factor = min($constraint / $width, $constraint / $height, 1);

					$html = Craft::$app->getView()->renderTemplate('_components/tools/cropper_modal',
						[
							'imageUrl' => UrlHelper::getResourceUrl('userphotos/temp/'.$userName.'/'.$filename),
							'width' => round($width * $factor),
							'height' => round($height * $factor),
							'factor' => $factor,
							'constraint' => $constraint
						]
					);

					return $this->asJson(['html' => $html]);
				}
			}
		}
		catch (Exception $exception)
		{
			Craft::error('There was an error uploading the photo: '.$exception->getMessage(), __METHOD__);
		}

		return $this->asErrorJson(Craft::t('app', 'There was an error uploading your photo.'));
	}

	/**
	 * Crop user photo.
	 *
	 * @return null
	 */
	public function actionCropUserPhoto()
	{
		$this->requireAjaxRequest();
		$this->requireLogin();

		$userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');

		if ($userId != Craft::$app->getUser()->getIdentity()->id)
		{
			$this->requirePermission('editUsers');
		}

		try
		{
			$x1 = Craft::$app->getRequest()->getRequiredBodyParam('x1');
			$x2 = Craft::$app->getRequest()->getRequiredBodyParam('x2');
			$y1 = Craft::$app->getRequest()->getRequiredBodyParam('y1');
			$y2 = Craft::$app->getRequest()->getRequiredBodyParam('y2');
			$source = Craft::$app->getRequest()->getRequiredBodyParam('source');

			// Strip off any querystring info, if any.
			$source = UrlHelper::stripQueryString($source);

			$user = Craft::$app->getUsers()->getUserById($userId);
			$userName = AssetsHelper::cleanAssetName($user->username, false);

			// make sure that this is this user's file
			$imagePath = Craft::$app->getPath()->getTempUploadsPath().'/userphotos/'.$userName.'/'.$source;

			if (IOHelper::fileExists($imagePath) && Craft::$app->getImages()->checkMemoryForImage($imagePath))
			{
				Craft::$app->getUsers()->deleteUserPhoto($user);

				$image = Craft::$app->getImages()->loadImage($imagePath);
				$image->crop($x1, $x2, $y1, $y2);

				if (Craft::$app->getUsers()->saveUserPhoto(IOHelper::getFilename($imagePath), $image, $user))
				{
					IOHelper::clearFolder(Craft::$app->getPath()->getTempUploadsPath().'/userphotos/'.$userName);

					$html = Craft::$app->getView()->renderTemplate('users/_userphoto',
						[
							'account' => $user
						]
					);

					return $this->asJson(['html' => $html]);
				}
			}

			IOHelper::clearFolder(Craft::$app->getPath()->getTempUploadsPath().'/userphotos/'.$userName);
		}
		catch (Exception $exception)
		{
			return $this->asErrorJson($exception->getMessage());
		}

		return $this->asErrorJson(Craft::t('app', 'Something went wrong when processing the photo.'));
	}

	/**
	 * Delete all the photos for current user.
	 *
	 * @return null
	 */
	public function actionDeleteUserPhoto()
	{
		$this->requireAjaxRequest();
		$this->requireLogin();
		$userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');

		if ($userId != Craft::$app->getUser()->getIdentity()->id)
		{
			$this->requirePermission('editUsers');
		}

		$user = Craft::$app->getUsers()->getUserById($userId);
		Craft::$app->getUsers()->deleteUserPhoto($user);

		$user->photo = null;
		Craft::$app->getUsers()->saveUser($user);

		$html = Craft::$app->getView()->renderTemplate('users/_userphoto',
			[
				'account' => $user
			]
		);

		return $this->asJson(['html' => $html]);
	}

	/**
	 * Sends a new activation email to a user.
	 *
	 * @return null
	 */
	public function actionSendActivationEmail()
	{
		$this->requirePostRequest();

		$userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');

		$user = User::find()
			->id($userId)
			->status(null)
			->withPassword()
			->one();

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		Craft::$app->getUsers()->sendActivationEmail($user);

		if (Craft::$app->getRequest()->getIsAjax())
		{
			$this->returnJson(array('success' => true));
		}
		else
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'Activation email sent.'));
			return $this->redirectToPostedUrl();
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
		$this->requirePermission('administrateUsers');

		$userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');
		$user = Craft::$app->getUsers()->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		// Even if you have administrateUsers permissions, only and admin should be able to unlock another admin.
		$currentUser = Craft::$app->getUser()->getIdentity();

		if ($user->admin && !$currentUser->admin)
		{
			throw new HttpException(403);
		}

		Craft::$app->getUsers()->unlockUser($user);

		Craft::$app->getSession()->setNotice(Craft::t('app', 'User activated.'));
		return $this->redirectToPostedUrl();
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
		$this->requirePermission('administrateUsers');

		$userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');
		$user = Craft::$app->getUsers()->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		// Even if you have administrateUsers permissions, only and admin should be able to suspend another admin.
		$currentUser = Craft::$app->getUser()->getIdentity();

		if ($user->admin && !$currentUser->admin)
		{
			throw new HttpException(403);
		}

		Craft::$app->getUsers()->suspendUser($user);

		Craft::$app->getSession()->setNotice(Craft::t('app', 'User suspended.'));
		return $this->redirectToPostedUrl();
	}

	/**
	 * Deletes a user.
	 */
	public function actionDeleteUser()
	{
		$this->requirePostRequest();
		$this->requireLogin();

		$this->requirePermission('deleteUsers');

		$userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');
		$user = Craft::$app->getUsers()->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		// Even if you have deleteUser permissions, only and admin should be able to delete another admin.
		if ($user->admin)
		{
			$this->requireAdmin();
		}

		// Are we transfering the user's content to a different user?
		$transferContentToId = Craft::$app->getRequest()->getBodyParam('transferContentTo');

		if (is_array($transferContentToId) && isset($transferContentToId[0]))
		{
			$transferContentToId = $transferContentToId[0];
		}

		if ($transferContentToId)
		{
			$transferContentTo = Craft::$app->getUsers()->getUserById($transferContentToId);

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
		if (Craft::$app->getUsers()->deleteUser($user, $transferContentTo))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'User deleted.'));
			return $this->redirectToPostedUrl();
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t delete the user.'));
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
		$this->requirePermission('administrateUsers');

		$userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');
		$user = Craft::$app->getUsers()->getUserById($userId);

		if (!$user)
		{
			$this->_noUserExists($userId);
		}

		// Even if you have administrateUsers permissions, only and admin should be able to un-suspend another admin.
		$currentUser = Craft::$app->getUser()->getIdentity();

		if ($user->admin && !$currentUser->admin)
		{
			throw new HttpException(403);
		}

		Craft::$app->getUsers()->unsuspendUser($user);

		Craft::$app->getSession()->setNotice(Craft::t('app', 'User unsuspended.'));
		return $this->redirectToPostedUrl();
	}

	/**
	 * Saves the asset field layout.
	 *
	 * @return null
	 */
	public function actionSaveFieldLayout()
	{
		$this->requirePostRequest();
		$this->requireAdmin();

		// Set the field layout
		$fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
		$fieldLayout->type = User::className();
		Craft::$app->getFields()->deleteLayoutsByType(User::className());

		if (Craft::$app->getFields()->saveLayout($fieldLayout))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'User fields saved.'));
			return $this->redirectToPostedUrl();
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save user fields.'));
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
			return $this->asJson(['success' => true]);
		}

		return $this->asErrorJson(Craft::t('app', 'Invalid password.'));
	}

	// Private Methods
	// =========================================================================

	/**
	 * Handles an invalid login attempt.
	 *
	 * @param string|null $authError
	 * @param User|null   $user
	 *
	 * @return Response|null
	 */
	private function _handleInvalidLogin($authError = null, User $user = null)
	{
		switch ($authError)
		{
			case User::AUTH_INVALID_CREDENTIALS:
			{
				$message = Craft::t('app', 'Invalid username or password.');
				break;
			}
			case User::AUTH_PENDING_VERIFICATION:
			{
				$message = Craft::t('app', 'Account has not been activated.');
				break;
			}
			case User::AUTH_ACCOUNT_LOCKED:
			{
				$message = Craft::t('app', 'Account locked.');
				break;
			}
			case User::AUTH_ACCOUNT_COOLDOWN:
			{
				$timeRemaining = $user->getRemainingCooldownTime();

				if ($timeRemaining)
				{
					$message = Craft::t('app', 'Account locked. Try again in {time}.', ['time' => $timeRemaining->humanDuration()]);
				}
				else
				{
					$message = Craft::t('app', 'Account locked.');
				}

				break;
			}
			case User::AUTH_PASSWORD_RESET_REQUIRED:
			{
				$message = Craft::t('app', 'You need to reset your password. Check your email for instructions.');
				Craft::$app->getUsers()->sendPasswordResetEmail($user);
				break;
			}
			case User::AUTH_ACCOUNT_SUSPENDED:
			{
				$message = Craft::t('app', 'Account suspended.');
				break;
			}
			case User::AUTH_NO_CP_ACCESS:
			{
				$message = Craft::t('app', 'You cannot access the CP with that account.');
				break;
			}
			case User::AUTH_NO_CP_OFFLINE_ACCESS:
			{
				$message = Craft::t('app', 'You cannot access the CP while the system is offline with that account.');
				break;
			}
			default:
			{
				$message = Craft::t('app', 'Invalid username or password.');
			}
		}

		if (Craft::$app->getRequest()->getIsAjax())
		{
			return $this->asJson([
				'errorCode' => $authError,
				'error' => $message
			]);
		}
		else
		{
			Craft::$app->getSession()->setError($message);

			Craft::$app->getUrlManager()->setRouteParams([
				'loginName'    => Craft::$app->getRequest()->getBodyParam('loginName'),
				'rememberMe'   => (bool) Craft::$app->getRequest()->getBodyParam('rememberMe'),
				'errorCode'    => $authError,
				'errorMessage' => $message,
			]);
		}
	}

	/**
	 * Redirects the user after a successful login attempt, or if they visited the Login page while they were already
	 * logged in.
	 *
	 * @param bool $setNotice Whether a flash notice should be set, if this isn't an Ajax request.
	 *
	 * @return Response
	 */
	private function _handleSuccessfulLogin($setNotice)
	{
		// Get the current user
		$currentUser = Craft::$app->getUser()->getIdentity();

		// If this is a CP request and they can access the control panel, set the default return URL to wherever
		// postCpLoginRedirect tells us
		if (Craft::$app->getRequest()->getIsCpRequest() && $currentUser->can('accessCp'))
		{
			$postCpLoginRedirect = Craft::$app->getConfig()->get('postCpLoginRedirect');
			$defaultReturnUrl = UrlHelper::getCpUrl($postCpLoginRedirect);
		}
		else
		{
			// Otherwise send them wherever postLoginRedirect tells us
			$postLoginRedirect = Craft::$app->getConfig()->get('postLoginRedirect');
			$defaultReturnUrl = UrlHelper::getSiteUrl($postLoginRedirect);
		}

		// Were they trying to access a URL beforehand?
		$returnUrl = Craft::$app->getUser()->getReturnUrl($defaultReturnUrl);

		// Clear it out
		Craft::$app->getUser()->removeReturnUrl();

		// If this was an Ajax request, just return success:true
		if (Craft::$app->getRequest()->getIsAjax())
		{
			return $this->asJson([
				'success' => true,
				'returnUrl' => $returnUrl
			]);
		}
		else
		{
			if ($setNotice)
			{
				Craft::$app->getSession()->setNotice(Craft::t('app', 'Logged in.'));
			}

			return $this->redirectToPostedUrl($currentUser, $returnUrl);
		}
	}

	/**
	 * Renders the Set Password template for a given user.
	 *
	 * @param User  $user
	 * @param array $variables
	 * @return Response
	 */
	private function _renderSetPasswordTemplate(User $user, $variables)
	{
		$pathService = Craft::$app->getPath();
		$configService = Craft::$app->getConfig();

		// If the user doesn't have CP access, see if a custom Set Password template exists
		if (!$user->can('accessCp'))
		{
			$pathService->setTemplatesPath($pathServices->getSiteTemplatesPath());
			$templatePath = $configService->getLocalized('setPasswordPath');

			if (Craft::$app->getView()->doesTemplateExist($templatePath))
			{
				return $this->renderTemplate($templatePath, $variables);
			}
		}

		// Otherwise go with the CP's template
		$pathService->setTemplatesPath($pathService->getCpTemplatesPath());
		$templatePath = $configService->getCpSetPasswordPath();
		return $this->renderTemplate($templatePath, $variables);
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
		throw new Exception(Craft::t('app', 'No user exists with the ID “{id}”.', ['id' => $userId]));
	}

	/**
	 * Verifies that the current user's password was submitted with the request.
	 *
	 * @return bool
	 */
	private function _verifyExistingPassword()
	{
		$currentUser = Craft::$app->getUser()->getIdentity();

		if (!$currentUser)
		{
			return false;
		}

		$currentHashedPassword = $currentUser->password;
		$currentPassword = Craft::$app->getRequest()->getRequiredParam('password');

		return Craft::$app->getSecurity()->validatePassword($currentPassword, $currentHashedPassword);
	}

	/**
	 * @param $user
	 *
	 * @return null
	 */
	private function _processUserPhoto($user)
	{
		// Delete their photo?
		if (Craft::$app->getRequest()->getBodyParam('deleteUserPhoto'))
		{
			Craft::$app->getUsers()->deleteUserPhoto($user);
		}

		// Did they upload a new one?
		if ($userPhoto = UploadedFile::getInstanceByName('userPhoto'))
		{
			Craft::$app->getUsers()->deleteUserPhoto($user);
			$image = Craft::$app->getImages()->loadImage($userPhoto->tempName);
			$imageWidth = $image->getWidth();
			$imageHeight = $image->getHeight();

			$dimension = min($imageWidth, $imageHeight);
			$horizontalMargin = ($imageWidth - $dimension) / 2;
			$verticalMargin = ($imageHeight - $dimension) / 2;
			$image->crop($horizontalMargin, $imageWidth - $horizontalMargin, $verticalMargin, $imageHeight - $verticalMargin);

			Craft::$app->getUsers()->saveUserPhoto($userPhoto->name, $image, $user);

			IOHelper::deleteFile($userPhoto->tempName);
		}
	}

	/**
	 * @param $user
	 *
	 * @return null
	 */
	private function _processUserGroupsPermissions($user)
	{
		// Save any user groups
		if (Craft::$app->getEdition() == Craft::Pro && Craft::$app->getUser()->checkPermission('assignUserPermissions'))
		{
			// Save any user groups
			$groupIds = Craft::$app->getRequest()->getBodyParam('groups');

			if ($groupIds !== null)
			{
				Craft::$app->getUserGroups()->assignUserToGroups($user->id, $groupIds);
			}

			// Save any user permissions
			if ($user->admin)
			{
				$permissions = [];
			}
			else
			{
				$permissions = Craft::$app->getRequest()->getBodyParam('permissions');
			}

			if ($permissions !== null)
			{
				Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $permissions);
			}
		}
	}

	/**
	 * @return array
	 * @throws HttpException
	 */
	private function _processTokenRequest()
	{
		if (!Craft::$app->getUser()->getIsGuest())
		{
			Craft::$app->getUser()->logout();
		}

		$id            = Craft::$app->getRequest()->getRequiredParam('id');
		$code          = Craft::$app->getRequest()->getRequiredParam('code');
		$isCodeValid   = false;

		$userToProcess = User::find()
			->id($id)
			->status(null)
			->withPassword()
			->one();

		if ($userToProcess)
		{
			// Fire a 'beforeVerifyUser' event
			Craft::$app->getUsers()->trigger(Users::EVENT_BEFORE_VERIFY_EMAIL, new UserEvent([
				'user' => $userToProcess
			]));

			$isCodeValid = Craft::$app->getUsers()->isVerificationCodeValidForUser($userToProcess, $code);
		}

		if (!$userToProcess || !$isCodeValid)
		{
			$this->_processInvalidToken($userToProcess);
		}

		// Fire an 'afterVerifyUser' event
		Craft::$app->getUsers()->trigger(Users::EVENT_AFTER_VERIFY_EMAIL, new UserEvent([
			'user' => $userToProcess
		]));

		return ['code' => $code, 'id' => $id, 'userToProcess' => $userToProcess];
	}

	/**
	 * @param User $user
	 *
	 * @throws HttpException
	 */
	private function _processInvalidToken($user)
	{
		$url = Craft::$app->getConfig()->getLocalized('invalidUserTokenPath');

		// TODO: Remove this code in Craft 4
		if ($url == '')
		{
			// Check the deprecated config setting.
			$url = Craft::$app->getConfig()->getLocalized('activateAccountFailurePath');

			if ($url)
			{
				Craft::$app->getDeprecator()->log('activateAccountFailurePath', 'The ‘activateAccountFailurePath’ has been deprecated. Use ‘invalidUserTokenPath’ instead.');
			}
		}

		if ($url != '')
		{
			return $this->redirect(UrlHelper::getSiteUrl($url));
		}
		else
		{
			if ($user && $user->can('accessCp'))
			{
				$url = UrlHelper::getCpUrl(Craft::$app->getConfig()->getLoginPath());
			}
			else
			{
				$url = UrlHelper::getSiteUrl(Craft::$app->getConfig()->getLoginPath());
			}

			throw new HttpException('200', Craft::t('app', 'Invalid verification code. Please [login or reset your password]({loginUrl}).', ['loginUrl' => $url]));
		}
	}

	/**
	 * Takes over after a user has been activated.
	 *
	 * @param User $user
	 */
	private function _onAfterActivateUser(User $user)
	{
		// Should we log them in?
		$loggedIn = false;

		if (Craft::$app->getConfig()->get('autoLoginAfterAccountActivation'))
		{
			$loggedIn = Craft::$app->getUser()->loginByUserId($user->id, false, true);
		}

		// Can they access the CP?
		if ($user->can('accessCp'))
		{
			$postCpLoginRedirect = Craft::$app->getConfig()->get('postCpLoginRedirect');
			$url = UrlHelper::getCpUrl($postCpLoginRedirect);
		}
		else
		{
			$activateAccountSuccessPath = Craft::$app->getConfig()->getLocalized('activateAccountSuccessPath');
			$url = UrlHelper::getSiteUrl($activateAccountSuccessPath);
		}

		return $this->redirect($url);
	}
}
