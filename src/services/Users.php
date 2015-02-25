<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\dates\DateInterval;
use craft\app\dates\DateTime;
use craft\app\db\Query;
use craft\app\enums\ElementType;
use craft\app\enums\UserStatus;
use craft\app\errors\Exception;
use craft\app\events\DeleteUserEvent;
use craft\app\events\UserEvent;
use craft\app\helpers\AssetsHelper;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\StringHelper;
use craft\app\helpers\TemplateHelper;
use craft\app\helpers\UrlHelper;
use craft\app\io\Image;
use craft\app\models\Password as PasswordModel;
use craft\app\models\User as UserModel;
use craft\app\records\User as UserRecord;
use yii\base\Component;

/**
 * The Users service provides APIs for managing users.
 *
 * An instance of the Users service is globally accessible in Craft via [[Application::users `Craft::$app->users`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Users extends Component
{
	// Constants
	// =========================================================================

	/**
     * @event UserEvent The event that is triggered before a user is saved.
     *
     * You may set [[UserEvent::performAction]] to `false` to prevent the user from getting saved.
     */
    const EVENT_BEFORE_SAVE_USER = 'beforeSaveUser';

	/**
     * @event UserEvent The event that is triggered after a user is saved.
     */
    const EVENT_AFTER_SAVE_USER = 'afterSaveUser';

	/**
     * @event UserEvent The event that is triggered before a user's email is verified.
     */
    const EVENT_BEFORE_VERIFY_EMAIL = 'beforeVerifyEmail';

	/**
     * @event UserEvent The event that is triggered after a user's email is verified.
     */
    const EVENT_AFTER_VERIFY_EMAIL = 'afterVerifyEmail';

	/**
     * @event UserEvent The event that is triggered before a user is activated.
     *
     * You may set [[UserEvent::performAction]] to `false` to prevent the user from getting activated.
     */
    const EVENT_BEFORE_ACTIVATE_USER = 'beforeActivateUser';

	/**
     * @event UserEvent The event that is triggered after a user is activated.
     */
    const EVENT_AFTER_ACTIVATE_USER = 'afterActivateUser';

	/**
     * @event UserEvent The event that is triggered before a user is unlocked.
     *
     * You may set [[UserEvent::performAction]] to `false` to prevent the user from getting unlocked.
     */
    const EVENT_BEFORE_UNLOCK_USER = 'beforeUnlockUser';

	/**
     * @event UserEvent The event that is triggered after a user is unlocked.
     */
    const EVENT_AFTER_UNLOCK_USER = 'afterUnlockUser';

	/**
     * @event UserEvent The event that is triggered before a user is suspended.
     *
     * You may set [[UserEvent::performAction]] to `false` to prevent the user from getting suspended.
     */
    const EVENT_BEFORE_SUSPEND_USER = 'beforeSuspendUser';

	/**
     * @event UserEvent The event that is triggered after a user is suspended.
     */
    const EVENT_AFTER_SUSPEND_USER = 'afterSuspendUser';

	/**
     * @event UserEvent The event that is triggered before a user is unsuspended.
     *
     * You may set [[UserEvent::performAction]] to `false` to prevent the user from getting unsuspended.
     */
    const EVENT_BEFORE_UNSUSPEND_USER = 'beforeUnsuspendUser';

	/**
     * @event UserEvent The event that is triggered after a user is unsuspended.
     */
    const EVENT_AFTER_UNSUSPEND_USER = 'afterUnsuspendUser';

	/**
     * @event DeleteUserEvent The event that is triggered before a user is deleted.
     *
     * You may set [[UserEvent::performAction]] to `false` to prevent the user from getting deleted.
     */
    const EVENT_BEFORE_DELETE_USER = 'beforeDeleteUser';

	/**
     * @event DeleteUserEvent The event that is triggered after a user is deleted.
     */
    const EVENT_AFTER_DELETE_USER = 'afterDeleteUser';

	/**
     * @event UserEvent The event that is triggered before a user's password is set.
     *
     * The new password will be accessible from [[UserModel::newPassword]].
     *
     * You may set [[UserEvent::performAction]] to `false` to prevent the user's password from getting set.
     */
    const EVENT_BEFORE_SET_PASSWORD = 'beforeSetPassword';

	/**
     * @event UserEvent The event that is triggered after a user's password is set.
     */
    const EVENT_AFTER_SET_PASSWORD = 'afterSetPassword';

	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_usersById;

	// Public Methods
	// =========================================================================

	/**
	 * Returns a user by their ID.
	 *
	 * ```php
	 * $user = Craft::$app->users->getUserById($userId);
	 * ```
	 *
	 * @param int $userId The user’s ID.
	 *
	 * @return UserModel|null The user with the given ID, or `null` if a user could not be found.
	 */
	public function getUserById($userId)
	{
		if (!isset($this->_usersById) || !array_key_exists($userId, $this->_usersById))
		{
			$userRecord = UserRecord::findOne($userId);

			if ($userRecord)
			{
				$this->_usersById[$userId] = UserModel::populateModel($userRecord);
			}
			else
			{
				$this->_usersById[$userId] = null;
			}
		}

		return $this->_usersById[$userId];
	}

	/**
	 * Returns a user by their username or email.
	 *
	 * ```php
	 * $user = Craft::$app->users->getUserByUsernameOrEmail($loginName);
	 * ```
	 *
	 * @param string $usernameOrEmail The user’s username or email.
	 *
	 * @return UserModel|null The user with the given username/email, or `null` if a user could not be found.
	 */
	public function getUserByUsernameOrEmail($usernameOrEmail)
	{
		$userRecord = UserRecord::find()
			->where(
				['or', 'username=:usernameOrEmail', 'email=:usernameOrEmail'],
				[':usernameOrEmail' => $usernameOrEmail]
			)
			->one();

		if ($userRecord)
		{
			return UserModel::populateModel($userRecord);
		}

		return null;
	}

	/**
	 * Returns a user by their email.
	 *
	 * ```php
	 * $user = Craft::$app->users->getUserByEmail($email);
	 * ```
	 *
	 * @param string $email The user’s email.
	 *
	 * @return UserModel|null The user with the given email, or `null` if a user could not be found.
	 */
	public function getUserByEmail($email)
	{
		$userRecord = UserRecord::findOne(['email' => $email]);

		if ($userRecord)
		{
			return UserModel::populateModel($userRecord);
		}

		return null;
	}

	/**
	 * Returns a user by their UID.
	 *
	 * ```php
	 * $user = Craft::$app->users->getUserByUid($userUid);
	 * ```
	 *
	 * @param int $uid The user’s UID.
	 *
	 * @return UserModel|null The user with the given UID, or `null` if a user could not be found.
	 */
	public function getUserByUid($uid)
	{
		$userRecord = UserRecord::findOne([
			'uid' => $uid
		]);

		if ($userRecord)
		{
			return UserModel::populateModel($userRecord);
		}

		return null;
	}

	/**
	 * Returns whether a verification code is valid for the given user.
	 *
	 * This method first checks if the code has expired past the
	 * [verificationCodeDuration](http://buildwithcraft.com/docs/config-settings#verificationCodeDuration) config
	 * setting. If it is still valid, then, the checks the validity of the contents of the code.
	 *
	 * @param UserModel $user The user to check the code for.
	 * @param string    $code The verification code to check for.
	 *
	 * @return bool Whether the code is still valid.
	 */
	public function isVerificationCodeValidForUser(UserModel $user, $code)
	{
		$valid = false;
		$userRecord = $this->_getUserRecordById($user->id);

		if ($userRecord)
		{
			$minCodeIssueDate = DateTimeHelper::currentUTCDateTime();
			$duration = new DateInterval(Craft::$app->config->get('verificationCodeDuration'));
			$minCodeIssueDate->sub($duration);

			$valid = $userRecord->verificationCodeIssuedDate > $minCodeIssueDate;

			if (!$valid)
			{
				// It's expired, go ahead and remove it from the record so if they click the link again, it'll throw an
				// Exception.
				$userRecord = $this->_getUserRecordById($user->id);
				$userRecord->verificationCodeIssuedDate = null;
				$userRecord->verificationCode = null;
				$userRecord->save();
			}
			else
			{
				if (Craft::$app->getSecurity()->validatePassword($code, $userRecord->verificationCode))
				{
					$valid = true;
				}
				else
				{
					$valid = false;
					Craft::warning('The verification code ('.$code.') given for userId: '.$user->id.' does not match the hash in the database.', __METHOD__);
				}
			}
		}
		else
		{
			Craft::warning('Could not find a user with id:'.$user->id.'.', __METHOD__);
		}

		return $valid;
	}

	/**
	 * Returns the “Client” user account, if it has been created yet.
	 *
	 * An exception will be thrown if this function is called from Craft Personal or Pro.
	 *
	 * ```php
	 * if (Craft::$app->getEdition() == Craft::Client)
	 * {
	 *     $clientAccount = Craft::$app->users->getClient();
	 * }
	 * ```
	 *
	 * @return UserModel|null The “Client” user account, or `null` if it hasn’t been created yet.
	 */
	public function getClient()
	{
		Craft::$app->requireEdition(Craft::Client, false);

		$criteria = Craft::$app->elements->getCriteria(ElementType::User);
		$criteria->client = true;
		$criteria->status = null;
		return $criteria->first();
	}

	/**
	 * Saves a new or existing user.
	 *
	 * ```php
	 * $user = new UserModel();
	 * $user->username  = 'tommy';
	 * $user->firstName = 'Tom';
	 * $user->lastName  = 'Foolery';
	 * $user->email     = 'tom@thefoolery.com';
	 *
	 * $user->getContent()->birthYear = 1812;
	 *
	 * $success = Craft::$app->users->saveUser($user);
	 *
	 * if (!$success)
	 * {
	 *     Craft::error('Couldn’t save the user "'.$user->username.'"', __METHOD__);
	 * }
	 * ```
	 *
	 * @param UserModel $user The user to be saved.
	 *
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function saveUser(UserModel $user)
	{
		$isNewUser = !$user->id;

		if (!$isNewUser)
		{
			$userRecord = $this->_getUserRecordById($user->id);

			if (!$userRecord)
			{
				throw new Exception(Craft::t('app', 'No user exists with the ID “{id}”.', ['id' => $user->id]));
			}

			$oldUsername = $userRecord->username;
		}
		else
		{
			$userRecord = new UserRecord();
		}

		// Set the user record attributes
		$userRecord->username              = $user->username;
		$userRecord->firstName             = $user->firstName;
		$userRecord->lastName              = $user->lastName;
		$userRecord->photo                 = $user->photo;
		$userRecord->email                 = $user->email;
		$userRecord->admin                 = $user->admin;
		$userRecord->client                = $user->client;
		$userRecord->passwordResetRequired = $user->passwordResetRequired;
		$userRecord->preferredLocale       = $user->preferredLocale;
		$userRecord->weekStartDay          = $user->weekStartDay;
		$userRecord->unverifiedEmail       = $user->unverifiedEmail;

		$this->_processSaveUserStatus($userRecord, $user->status);

		$userRecord->validate();
		$user->addErrors($userRecord->getErrors());

		if (Craft::$app->getEdition() == Craft::Pro)
		{
			// Validate any content.
			if (!Craft::$app->content->validateContent($user))
			{
				$user->addErrors($user->getContent()->getErrors());
			}
		}

		// If newPassword is set at all, even to an empty string, validate & set it.
		if ($user->newPassword !== null)
		{
			$this->_setPasswordOnUserRecord($user, $userRecord);
		}

		if ($user->hasErrors())
		{
			return false;
		}

		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

		try
		{
			// Set a default status of pending, if one wasn't supplied.
			if (!$user->status)
			{
				$user->pending = true;
			}

			// Fire a 'beforeSaveUser' event
			$event = new UserEvent([
				'user' => $user
			]);

			$this->trigger(static::EVENT_BEFORE_SAVE_USER, $event);

			// Is the event is giving us the go-ahead?
			if ($event->performAction)
			{
				// Save the element
				$success = Craft::$app->elements->saveElement($user, false);

				// If it didn't work, rollback the transaction in case something changed in onBeforeSaveUser
				if (!$success)
				{
					if ($transaction !== null)
					{
						$transaction->rollback();
					}

					return false;
				}

				// Now that we have an element ID, save it on the other stuff
				if ($isNewUser)
				{
					$userRecord->id = $user->id;
				}

				$userRecord->save(false);

				if (!$isNewUser)
				{
					// Has the username changed?
					if ($user->username != $oldUsername)
					{
						// Rename the user's photo directory
						$oldFolder = Craft::$app->path->getUserPhotosPath().'/'.$oldUsername;
						$newFolder = Craft::$app->path->getUserPhotosPath().'/'.$user->username;

						if (IOHelper::folderExists($newFolder))
						{
							IOHelper::deleteFolder($newFolder);
						}

						if (IOHelper::folderExists($oldFolder))
						{
							IOHelper::rename($oldFolder, $newFolder);
						}
					}
				}
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we saved the user, in case something changed
			// in onBeforeSaveUser
			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		if ($success)
		{
			// Fire an 'afterSaveUser' event
			$this->trigger(static::EVENT_AFTER_SAVE_USER, new AssetEvent([
				'user' => $user
			]));
		}

		return $success;
	}

	/**
	 * Sends a new account activation email for a user, regardless of their status.
	 *
	 * A new verification code will generated for the user overwriting any existing one.
	 *
	 * @param UserModel $user The user to send the activation email to.
	 *
	 * @return bool Whether the email was sent successfully.
	 */
	public function sendActivationEmail(UserModel $user)
	{
		// If the user doesn't have a password yet, use a Password Reset URL
		if (!$user->password)
		{
			$url = $this->getPasswordResetUrl($user);
		}
		else
		{
			$url = $this->getEmailVerifyUrl($user);
		}

		return Craft::$app->email->sendEmailByKey($user, 'account_activation', [
			'link' => TemplateHelper::getRaw($url),
		]);
	}

	/**
	 * Sends a new email verification email to a user, regardless of their status.
	 *
	 * A new verification code will generated for the user overwriting any existing one.
	 *
	 * @param UserModel $user The user to send the activation email to.
	 *
	 * @return bool Whether the email was sent successfully.
	 */
	public function sendNewEmailVerifyEmail(UserModel $user)
	{
		$url = $this->getEmailVerifyUrl($user);

		return Craft::$app->email->sendEmailByKey($user, 'verify_new_email', [
			'link' => TemplateHelper::getRaw($url),
		]);
	}

	/**
	 * Sends a password reset email to a user.
	 *
	 * A new verification code will generated for the user overwriting any existing one.
	 *
	 * @param UserModel $user The user to send the forgot password email to.
	 *
	 * @return bool Whether the email was sent successfully.
	 */
	public function sendPasswordResetEmail(UserModel $user)
	{
		$url = $this->getPasswordResetUrl($user);

		return Craft::$app->email->sendEmailByKey($user, 'forgot_password', [
			'link' => TemplateHelper::getRaw($url),
		]);
	}

	/**
	 * Sets a new verification code on a user, and returns their new Email Verification URL.
	 *
	 * @param UserModel $user The user that should get the new Email Verification URL.
	 *
	 * @return string The new Email Verification URL.
	 */
	public function getEmailVerifyUrl(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);
		$unhashedVerificationCode = $this->_setVerificationCodeOnUserRecord($userRecord);
		$userRecord->save();

		if ($user->can('accessCp'))
		{
			$url = UrlHelper::getActionUrl('users/verifyemail', ['code' => $unhashedVerificationCode, 'id' => $userRecord->uid], Craft::$app->getRequest()->getIsSecureConnection() ? 'https' : 'http');
		}
		else
		{
			// We want to hide the CP trigger if they don't have access to the CP.
			$path = Craft::$app->config->get('actionTrigger').'/users/verifyemail';
			$url = UrlHelper::getSiteUrl($path, ['code' => $unhashedVerificationCode, 'id' => $userRecord->uid], Craft::$app->getRequest()->getIsSecureConnection() ? 'https' : 'http');
		}

		return $url;
	}

	/**
	 * Sets a new verification code on a user, and returns their new Password Reset URL.
	 *
	 * @param UserModel $user The user that should get the new Password Reset URL
	 *
	 * @return string The new Password Reset URL.
	 */
	public function getPasswordResetUrl(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);
		$unhashedVerificationCode = $this->_setVerificationCodeOnUserRecord($userRecord);
		$userRecord->save();

		if ($user->can('accessCp'))
		{
			$url = UrlHelper::getActionUrl('users/setpassword', ['code' => $unhashedVerificationCode, 'id' => $userRecord->uid], Craft::$app->getRequest()->getIsSecureConnection() ? 'https' : 'http');
		}
		else
		{
			// We want to hide the CP trigger if they don't have access to the CP.
			$path = Craft::$app->config->get('actionTrigger').'/users/setpassword';
			$url = UrlHelper::getSiteUrl($path, ['code' => $unhashedVerificationCode, 'id' => $userRecord->uid], Craft::$app->getRequest()->getIsSecureConnection() ? 'https' : 'http');
		}

		return $url;
	}

	/**
	 * Crops and saves a user’s photo.
	 *
	 * @param string    $fileName The name of the file.
	 * @param Image     $image    The image.
	 * @param UserModel $user     The user.
	 *
	 * @throws \Exception
	 * @return bool Whether the photo was saved successfully.
	 */
	public function saveUserPhoto($fileName, Image $image, UserModel $user)
	{
		$userName = IOHelper::cleanFilename($user->username);
		$userPhotoFolder = Craft::$app->path->getUserPhotosPath().'/'.$userName;
		$targetFolder = $userPhotoFolder.'/original';

		IOHelper::ensureFolderExists($userPhotoFolder);
		IOHelper::ensureFolderExists($targetFolder);

		$targetPath = $targetFolder.'/'.AssetsHelper::cleanAssetName($fileName);

		$result = $image->saveAs($targetPath);

		if ($result)
		{
			IOHelper::changePermissions($targetPath, Craft::$app->config->get('defaultFilePermissions'));
			$record = UserRecord::findOne($user->id);
			$record->photo = $fileName;
			$record->save();

			$user->photo = $fileName;

			return true;
		}

		return false;
	}

	/**
	 * Deletes a user's photo.
	 *
	 * @param UserModel $user The user.
	 *
	 * @return null
	 */
	public function deleteUserPhoto(UserModel $user)
	{
		$folder = Craft::$app->path->getUserPhotosPath().'/'.$user->username;

		if (IOHelper::folderExists($folder))
		{
			IOHelper::deleteFolder($folder);
		}

		$record = UserRecord::findOne($user->id);
		$record->photo = null;
		$user->photo = null;
		$record->save();
	}

	/**
	 * Changes a user’s password.
	 *
	 * @param UserModel $user           The user.
	 * @param bool      $forceDifferent Whether to force the new password to be different than any existing password.
	 *
	 * @return bool Whether the user’s new password was saved successfully.
	 */
	public function changePassword(UserModel $user, $forceDifferent = false)
	{
		$userRecord = $this->_getUserRecordById($user->id);

		if ($this->_setPasswordOnUserRecord($user, $userRecord, true, $forceDifferent))
		{
			$userRecord->save();
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Updates a user's record for a successful login.
	 *
	 * @param UserModel $user
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function updateUserLoginInfo(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);

		$userRecord->lastLoginDate = $user->lastLoginDate = DateTimeHelper::currentUTCDateTime();
		$userRecord->lastLoginAttemptIPAddress = Craft::$app->getRequest()->getUserIP();
		$userRecord->invalidLoginWindowStart = null;
		$userRecord->invalidLoginCount = $user->invalidLoginCount = null;
		$userRecord->verificationCode = null;
		$userRecord->verificationCodeIssuedDate = null;

		return $userRecord->save();
	}

	/**
	 * Handles an invalid login for a user.
	 *
	 * @param UserModel $user The user.
	 *
	 * @return bool Whether the user’s record was updated successfully.
	 */
	public function handleInvalidLogin(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);
		$currentTime = DateTimeHelper::currentUTCDateTime();

		$userRecord->lastInvalidLoginDate = $user->lastInvalidLoginDate = $currentTime;
		$userRecord->lastLoginAttemptIPAddress = Craft::$app->getRequest()->getUserIP();

		$maxInvalidLogins = Craft::$app->config->get('maxInvalidLogins');

		if ($maxInvalidLogins)
		{
			if ($this->_isUserInsideInvalidLoginWindow($userRecord))
			{
				$userRecord->invalidLoginCount++;

				// Was that one bad password too many?
				if ($userRecord->invalidLoginCount > $maxInvalidLogins)
				{
					$userRecord->locked = true;
					$user->locked = true;
					$userRecord->invalidLoginCount = null;
					$userRecord->invalidLoginWindowStart = null;
					$userRecord->lockoutDate = $user->lockoutDate = $currentTime;
				}
			}
			else
			{
				// Start the invalid login window and counter
				$userRecord->invalidLoginWindowStart = $currentTime;
				$userRecord->invalidLoginCount = 1;
			}

			// Update the counter on the user model
			$user->invalidLoginCount = $userRecord->invalidLoginCount;
		}

		return $userRecord->save();
	}

	/**
	 * Activates a user, bypassing email verification.
	 *
	 * @param UserModel $user The user.
	 *
	 * @throws \Exception
	 * @return bool Whether the user was activated successfully.
	 */
	public function activateUser(UserModel $user)
	{
		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

		try
		{
			// Fire a 'beforeActivateUser' event
			$event = new UserEvent([
				'user' => $user,
			]);

			$this->trigger(static::EVENT_BEFORE_ACTIVATE_USER, $event);

			// Is the event is giving us the go-ahead?
			if ($event->performAction)
			{
				$userRecord = $this->_getUserRecordById($user->id);

				$userRecord->setActive();
				$user->setActive();
				$userRecord->verificationCode = null;
				$userRecord->verificationCodeIssuedDate = null;
				$userRecord->lockoutDate = null;
				$userRecord->save();

				// If they have an unverified email address, now is the time to set it to their primary email address
				$this->verifyEmailForUser($user);
				$success = true;
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we activated the user, in case something changed
			// in onBeforeActivateUser
			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		if ($success)
		{
			// Fire an 'afterActivateUser' event
			$this->trigger(static::EVENT_AFTER_ACTIVATE_USER, new UserEvent([
				'user' => $user
			]));
		}

		return $success;
	}

	/**
	 * If 'unverifiedEmail' is set on the UserModel, then this method will transfer it to the official email property
	 * and clear the unverified one.
	 *
	 * @param UserModel $user
	 */
	public function verifyEmailForUser(UserModel $user)
	{
		if ($user->unverifiedEmail)
		{
			$userRecord = $this->_getUserRecordById($user->id);
			$userRecord->email = $user->unverifiedEmail;

			if (Craft::$app->config->get('useEmailAsUsername'))
			{
				$userRecord->username = $user->unverifiedEmail;
			}

			$userRecord->unverifiedEmail = null;
			$userRecord->save();

			// If the user status is pending, let's activate them.
			if ($userRecord->pending == true)
			{
				$this->activateUser($user);
			}
		}
	}

	/**
	 * Unlocks a user, bypassing the cooldown phase.
	 *
	 * @param UserModel $user The user.
	 *
	 * @throws \Exception
	 * @return bool Whether the user was unlocked successfully.
	 */
	public function unlockUser(UserModel $user)
	{
		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

		try
		{
			// Fire a 'beforeUnlockUser' event
			$event = new UserEvent([
				'user' => $user,
			]);

			$this->trigger(static::EVENT_BEFORE_UNLOCK_USER, $event);

			// Is the event is giving us the go-ahead?
			if ($event->performAction)
			{
				$userRecord = $this->_getUserRecordById($user->id);

				$userRecord->locked = false;
				$user->locked = false;

				$userRecord->invalidLoginCount = $user->invalidLoginCount = null;
				$userRecord->invalidLoginWindowStart = null;

				$userRecord->save();
				$success = true;
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we unlocked the user, in case something changed
			// in onBeforeUnlockUser
			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		if ($success)
		{
			// Fire an 'afterUnlockUser' event
			$this->trigger(event::EVENT_AFTER_UNLOCK_USER, new UserEvent([
				'user' => $user
			]));
		}

		return $success;
	}

	/**
	 * Suspends a user.
	 *
	 * @param UserModel $user The user.
	 *
	 * @throws \Exception
	 * @return bool Whether the user was suspended successfully.
	 */
	public function suspendUser(UserModel $user)
	{
		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

		try
		{
			// Fire a 'beforeSuspendUser' event
			$event = new UserEvent([
				'user' => $user,
			]);

			$this->trigger(static::EVENT_BEFORE_SUSPEND_USER, $event);

			// Is the event is giving us the go-ahead?
			if ($event->performAction)
			{
				$userRecord = $this->_getUserRecordById($user->id);

				$userRecord->suspended = true;
				$user->suspended = true;

				$userRecord->save();
				$success = true;
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we saved the user, in case something changed
			// in onBeforeSuspendUser
			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		if ($success)
		{
			// Fire an 'afterSuspendUser' event
			$this->trigger(static::EVENT_AFTER_SUSPEND_USER, new UserEvent([
				'user' => $user
			]));
		}

		return $success;
	}

	/**
	 * Unsuspends a user.
	 *
	 * @param UserModel $user The user.
	 *
	 * @throws \Exception
	 * @return bool Whether the user was unsuspended successfully.
	 */
	public function unsuspendUser(UserModel $user)
	{
		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

		try
		{
			// Fire a 'beforeUnsuspendUser' event
			$event = new UserEvent([
				'user' => $user,
			]);

			$this->trigger(static::EVENT_BEFORE_UNSUSPEND_USER, $event);

			// Is the event is giving us the go-ahead?
			if ($event->performAction)
			{
				$userRecord = $this->_getUserRecordById($user->id);

				$userRecord->suspended = false;
				$user->suspended = false;

				$userRecord->save();
				$success = true;
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we unsuspended the user, in case something changed
			// in onBeforeUnsuspendUser
			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		if ($success)
		{
			// Fire an 'afterUnsuspendUser' event
			$this->trigger(static::EVENT_AFTER_UNSUSPEND_USER, new UserEvent([
				'user' => $user
			]));
		}

		return $success;
	}

	/**
	 * Deletes a user.
	 *
	 * @param UserModel      $user              The user to be deleted.
	 * @param UserModel|null $transferContentTo The user who should take over the deleted user’s content.
	 *
	 * @throws \Exception
	 * @return bool Whether the user was deleted successfully.
	 */
	public function deleteUser(UserModel $user, UserModel $transferContentTo = null)
	{
		if (!$user->id)
		{
			return false;
		}

		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

		try
		{
			// Fire a 'beforeDeleteUser' event
			$event = new DeleteUserEvent([
				'user'              => $user,
				'transferContentTo' => $transferContentTo
			]);

			$this->trigger(static::EVENT_BEFORE_DELETE_USER, $event);

			// Is the event is giving us the go-ahead?
			if ($event->performAction)
			{
				// Get the entry IDs that belong to this user
				$entryIds = (new Query())
					->select('id')
					->from('{{%entries}}')
					->where(['authorId' => $user->id])
					->column();

				// Should we transfer the content to a new user?
				if ($transferContentTo)
				{
					// Delete the template caches for any entries authored by this user
					Craft::$app->templateCache->deleteCachesByElementId($entryIds);

					// Update the entry/version/draft tables to point to the new user
					$userRefs = [
						'{{%entries}}' => 'authorId',
						'{{%entrydrafts}}' => 'creatorId',
						'{{%entryversions}}' => 'creatorId',
					];

					foreach ($userRefs as $table => $column)
					{
						Craft::$app->getDb()->createCommand()->update($table, [
							$column => $transferContentTo->id
						], [
							$column => $user->id
						])->execute();
					}
				}
				else
				{
					// Delete the entries
					Craft::$app->elements->deleteElementById($entryIds);
				}

				// Delete the user
				$success = Craft::$app->elements->deleteElementById($user->id);

				// If it didn't work, rollback the transaction in case something changed in onBeforeDeleteUser
				if (!$success)
				{
					if ($transaction !== null)
					{
						$transaction->rollback();
					}

					return false;
				}
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we deleted the user,
			// in case something changed in onBeforeDeleteUser
			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		if ($success)
		{
			// Fire an 'afterDeleteUser' event
			$this->trigger(static::EVENT_AFTER_DELETE_USER, new DeleteUserEvent([
				'user'              => $user,
				'transferContentTo' => $transferContentTo
			]));
		}

		return $success;
	}

	/**
	 * Shuns a message for a user.
	 *
	 * @param int      $userId     The user’s ID.
	 * @param string   $message    The message to be shunned.
	 * @param DateTime $expiryDate When the message should be un-shunned. Defaults to `null` (never un-shun).
	 *
	 * @return bool Whether the message was shunned successfully.
	 */
	public function shunMessageForUser($userId, $message, $expiryDate = null)
	{
		if ($expiryDate instanceof \DateTime)
		{
			$expiryDate = DateTimeHelper::formatTimeForDb($expiryDate->getTimestamp());
		}
		else
		{
			$expiryDate = null;
		}

		$affectedRows = Craft::$app->getDb()->createCommand()->insertOrUpdate('shunnedmessages', [
			'userId'  => $userId,
			'message' => $message
		], [
			'expiryDate' => $expiryDate
		])->execute();

		return (bool) $affectedRows;
	}

	/**
	 * Un-shuns a message for a user.
	 *
	 * @param int    $userId  The user’s ID.
	 * @param string $message The message to un-shun.
	 *
	 * @return bool Whether the message was un-shunned successfully.
	 */
	public function unshunMessageForUser($userId, $message)
	{
		$affectedRows = Craft::$app->getDb()->createCommand()->delete('{{%shunnedmessages}}', [
			'userId'  => $userId,
			'message' => $message
		])->execute();

		return (bool) $affectedRows;
	}

	/**
	 * Returns whether a message is shunned for a user.
	 *
	 * @param int    $userId  The user’s ID.
	 * @param string $message The message to check.
	 *
	 * @return bool Whether the user has shunned the message.
	 */
	public function hasUserShunnedMessage($userId, $message)
	{
		return (new Query())
			->from('{{%shunnedmessages}}')
			->where(['and',
				'userId = :userId',
				'message = :message',
				['or', 'expiryDate IS NULL', 'expiryDate > :now']
			], [
				':userId'  => $userId,
				':message' => $message,
				':now'     => DateTimeHelper::formatTimeForDb()
			])
			->exists();
	}

	/**
	 * Sets a new verification code on the user's record.
	 *
	 * @param UserModel $user The user.
	 *
	 * @return string The user’s brand new verification code.
	 */
	public function setVerificationCodeOnUser(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);
		$unhashedVerificationCode = $this->_setVerificationCodeOnUserRecord($userRecord);
		$userRecord->save();

		return $unhashedVerificationCode;
	}

	/**
	 * Deletes any pending users that have shown zero sense of urgency and are just taking up space.
	 *
	 * This method will check the
	 * [purgePendingUsersDuration](http://buildwithcraft.com/docs/config-settings#purgePendingUsersDuration) config
	 * setting, and if it is set to a valid duration, it will delete any user accounts that were created that duration
	 * ago, and have still not activated their account.
	 *
	 * @return null
	 */
	public function purgeExpiredPendingUsers()
	{
		if (($duration = Craft::$app->config->get('purgePendingUsersDuration')) !== false)
		{
			$interval = new DateInterval($duration);
			$expire = DateTimeHelper::currentUTCDateTime();
			$pastTimeStamp = $expire->sub($interval)->getTimestamp();
			$pastTime = DateTimeHelper::formatTimeForDb($pastTimeStamp);

			$ids = (new Query())
				->select('id')
				->from('{{%users}}')
				->where(['and', 'pending=1', 'verificationCodeIssuedDate < :pastTime'], [':pastTime' => $pastTime])
				->column();

			$affectedRows = Craft::$app->getDb()->createCommand()->delete('{{%elements}}', ['in', 'id', $ids])->execute();

			if ($affectedRows > 0)
			{
				Craft::info('Just deleted '.$affectedRows.' pending users from the users table, because the were more than '.$duration.' old.', __METHOD__);
			}
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Gets a user record by its ID.
	 *
	 * @param int $userId
	 *
	 * @throws Exception
	 * @return UserRecord
	 */
	private function _getUserRecordById($userId)
	{
		$userRecord = UserRecord::findOne($userId);

		if (!$userRecord)
		{
			throw new Exception(Craft::t('app', 'No user exists with the ID “{id}”.', ['id' => $userId]));
		}

		return $userRecord;
	}

	/**
	 * Sets a user record up for a new verification code without saving it.
	 *
	 * @param  UserRecord $userRecord
	 *
	 * @return string
	 */
	private function _setVerificationCodeOnUserRecord(UserRecord $userRecord)
	{
		$unhashedCode = StringHelper::UUID();
		$hashedCode = Craft::$app->getSecurity()->hashPassword($unhashedCode);
		$userRecord->verificationCode = $hashedCode;
		$userRecord->verificationCodeIssuedDate = DateTimeHelper::currentUTCDateTime();

		return $unhashedCode;
	}

	/**
	 * Determines if a user is within their invalid login window.
	 *
	 * @param UserRecord $userRecord
	 *
	 * @return bool
	 */
	private function _isUserInsideInvalidLoginWindow(UserRecord $userRecord)
	{
		if ($userRecord->invalidLoginWindowStart)
		{
			$duration = new DateInterval(Craft::$app->config->get('invalidLoginWindowDuration'));
			$end = $userRecord->invalidLoginWindowStart->add($duration);
			return ($end >= DateTimeHelper::currentUTCDateTime());
		}
		else
		{
			return false;
		}
	}

	/**
	 * Sets a user record up for a new password without saving it.
	 *
	 * @param UserModel  $user                        The user who is getting a new password.
	 * @param UserRecord $userRecord                  The user’s record.
	 * @param bool       $updatePasswordResetRequired Whether the user’s
	 *                                                [[UserModel::passwordResetRequired passwordResetRequired]]
	 *                                                attribute should be set `false`. Default is `true`.
	 * @param bool       $forceDifferentPassword      Whether to force a new password to be different from any existing
	 *                                                password.
	 *
	 * @return bool
	 */
	private function _setPasswordOnUserRecord(UserModel $user, UserRecord $userRecord, $updatePasswordResetRequired = true, $forceDifferentPassword = false)
	{
		// Validate the password first
		$passwordModel = new PasswordModel();
		$passwordModel->password = $user->newPassword;

		$validates = false;

		// If it's a new user AND we allow public registration, set it on the 'password' field and not 'newpassword'.
		if (!$user->id && Craft::$app->systemSettings->getSetting('users', 'allowPublicRegistration'))
		{
			$passwordErrorField = 'password';
		}
		else
		{
			$passwordErrorField = 'newPassword';
		}

		if ($passwordModel->validate())
		{
			if ($forceDifferentPassword)
			{
				// See if the passwords are the same.
				if (Craft::$app->getSecurity()->validatePassword($user->newPassword, $userRecord->password))
				{
					$user->addErrors([
						$passwordErrorField => Craft::t('app', 'That password is the same as your old password. Please choose a new one.'),
					]);
				}
				else
				{
					$validates = true;
				}
			}
			else
			{
				$validates = true;
			}

			if ($validates)
			{
				// Fire a 'beforeSetPassword' event
				$event = new UserEvent([
					'user' => $user
				]);

				$this->trigger(static::EVENT_BEFORE_SET_PASSWORD, $event);

				// Is the event is giving us the go-ahead?
				$validates = $event->performAction;
			}
		}

		if ($validates)
		{
			$hash = Craft::$app->getSecurity()->hashPassword($user->newPassword);

			$userRecord->password = $user->password = $hash;
			$userRecord->invalidLoginWindowStart = null;
			$userRecord->invalidLoginCount = $user->invalidLoginCount = null;
			$userRecord->verificationCode = null;
			$userRecord->verificationCodeIssuedDate = null;

			// If it's an existing user, reset the passwordResetRequired bit.
			if ($updatePasswordResetRequired && $user->id)
			{
				$userRecord->passwordResetRequired = $user->passwordResetRequired = false;
			}

			$userRecord->lastPasswordChangeDate = $user->lastPasswordChangeDate = DateTimeHelper::currentUTCDateTime();

			$user->newPassword = null;

			$success = true;
		}
		else
		{
			$user->addErrors([
				$passwordErrorField => $passwordModel->getErrors('password')
			]);

			$success = false;
		}

		if ($success)
		{
			// Fire an 'afterSetPassword' event
			$this->trigger(static::EVENT_AFTER_SET_PASSWORD, new UserEvent([
				'user' => $user
			]));

		}

		return $success;
	}

	/**
	 * @param $userRecord
	 * @param $status
	 */
	private function _processSaveUserStatus($userRecord, $status)
	{
		switch ($status)
		{
			case UserStatus::Active:
			{
				$userRecord->archived = false;
				$userRecord->locked = false;
				$userRecord->pending = false;
				$userRecord->suspended = false;

				break;
			}

			case UserStatus::Pending:
			{
				$userRecord->pending = true;
				break;
			}

			case UserStatus::Locked:
			{
				$userRecord->locked = true;
				break;
			}

			case UserStatus::Suspended:
			{
				$userRecord->suspended = true;
				break;
			}

			case UserStatus::Archived:
			{
				$userRecord->archived = true;
				break;
			}
		}
	}
}
