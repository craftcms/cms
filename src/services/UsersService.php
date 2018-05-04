<?php
namespace Craft;

/**
 * UsersService provides APIs for managing users.
 *
 * An instance of UsersService is globally accessible in Craft via {@link WebApp::users `craft()->users`}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class UsersService extends BaseApplicationComponent
{
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
	 * $user = craft()->users->getUserById($userId);
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
			$userRecord = UserRecord::model()->findById($userId);

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
	 * $user = craft()->users->getUserByUsernameOrEmail($loginName);
	 * ```
	 *
	 * @param string $usernameOrEmail The user’s username or email.
	 *
	 * @return UserModel|null The user with the given username/email, or `null` if a user could not be found.
	 */
	public function getUserByUsernameOrEmail($usernameOrEmail)
	{
		$userRecord = UserRecord::model()->find(array(
			'condition' => 'username=:usernameOrEmail OR email=:usernameOrEmail',
			'params' => array(':usernameOrEmail' => $usernameOrEmail),
		));

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
	 * $user = craft()->users->getUserByEmail($email);
	 * ```
	 *
	 * @param string $email The user’s email.
	 *
	 * @return UserModel|null The user with the given email, or `null` if a user could not be found.
	 */
	public function getUserByEmail($email)
	{
		$userRecord = UserRecord::model()->find(array(
			'condition' => 'email=:email',
			'params' => array(':email' => $email),
		));

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
	 * $user = craft()->users->getUserByUid($userUid);
	 * ```
	 *
	 * @param int $uid The user’s UID.
	 *
	 * @return UserModel|null The user with the given UID, or `null` if a user could not be found.
	 */
	public function getUserByUid($uid)
	{
		$userRecord = UserRecord::model()->findByAttributes(array(
			'uid' => $uid
		));

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
	 * [verificationCodeDuration](http://craftcms.com/docs/config-settings#verificationCodeDuration) config
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
			$duration = new DateInterval(craft()->config->get('verificationCodeDuration'));
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
				if (craft()->security->checkPassword($code, $userRecord->verificationCode))
				{
					$valid = true;
				}
				else
				{
					$valid = false;
					Craft::log('The verification code ('.$code.') given for userId: '.$user->id.' does not match the hash in the database.', LogLevel::Warning);
				}
			}
		}
		else
		{
			Craft::log('Could not find a user with id:'.$user->id.'.', LogLevel::Warning);
		}

		return $valid;
	}

	/**
	 * Returns the “Client” user account, if it has been created yet.
	 *
	 * An exception will be thrown if this function is called from Craft Personal or Pro.
	 *
	 * ```php
	 * if (craft()->getEdition() == Craft::Client)
	 * {
	 *     $clientAccount = craft()->users->getClient();
	 * }
	 * ```
	 *
	 * @return UserModel|null The “Client” user account, or `null` if it hasn’t been created yet.
	 */
	public function getClient()
	{
		craft()->requireEdition(Craft::Client, false);

		$criteria = craft()->elements->getCriteria(ElementType::User);
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
	 * $success = craft()->users->saveUser($user);
	 *
	 * if (!$success)
	 * {
	 *     Craft::log('Couldn’t save the user "'.$user->username.'"', LogLevel::Error);
	 * }
	 * ```
	 *
	 * @param UserModel $user The user to be saved.
	 *
	 * @throws \Exception
	 * @return bool Whether the user was saved successfully.
	 */
	public function saveUser(UserModel $user)
	{
		$isNewUser = !$user->id;

		if (!$isNewUser)
		{
			$userRecord = $this->_getUserRecordById($user->id);

			if (!$userRecord)
			{
				throw new Exception(Craft::t('No user exists with the ID “{id}”.', array('id' => $user->id)));
			}

			$oldUsername = $userRecord->username;
		}
		else
		{
			$userRecord = new UserRecord();
			$userRecord->pending = true;
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

		$userRecord->validate();
		$user->addErrors($userRecord->getErrors());

		if (craft()->getEdition() == Craft::Pro)
		{
			// Validate any content.
			if (!craft()->content->validateContent($user))
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

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			// Fire an 'onBeforeSaveUser' event
			$event = new Event($this, array(
				'user'      => $user,
				'isNewUser' => $isNewUser
			));

			$this->onBeforeSaveUser($event);

			// Is the event is giving us the go-ahead?
			if ($event->performAction)
			{
				// Save the element
				$success = craft()->elements->saveElement($user, false);

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
						$cleanOldUsername = AssetsHelper::cleanAssetName($oldUsername, false, true);
						$cleanUsername = AssetsHelper::cleanAssetName($user->username, false, true);
						$oldFolder = craft()->path->getUserPhotosPath().$cleanOldUsername;
						$newFolder = craft()->path->getUserPhotosPath().$cleanUsername;

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
			// Fire an 'onSaveUser' event
			$this->onSaveUser(new Event($this, array(
				'user'      => $user,
				'isNewUser' => $isNewUser
			)));

			if ($this->hasEventHandler('onSaveProfile'))
			{
				// Fire an 'onSaveProfile' event (deprecated)
				$this->onSaveProfile(new Event($this, array(
					'user' => $user
				)));
			}

			// They got unsuspended
			if ($userRecord->suspended == true && $user->suspended == false)
			{
				$this->unsuspendUser($user);
			}
			// They got suspended
			else if ($userRecord->suspended == false && $user->suspended == true)
			{
				$this->suspendUser($user);
			}

			// They got activated
			if ($userRecord->pending == true && $user->pending == false)
			{
				$this->activateUser($user);
			}
		}

		return $success;
	}

	/**
	 * Saves a user's profile.
	 *
	 * @param UserModel $user
	 *
	 * @deprecated Deprecated in 2.0. Use {@link saveUser()} instead.
	 * @return bool
	 */
	public function saveProfile(UserModel $user)
	{
		craft()->deprecator->log('UsersService::saveProfile()', 'UsersService::saveProfile() has been deprecated. Use saveUser() instead.');
		return $this->saveUser($user);
	}

	/**
	 * Fires an 'onSaveProfile' event.
	 *
	 * @param Event $event
	 *
	 * @deprecated Deprecated in 2.0. Use {@link onSaveUser() `users.onSaveUser`} instead.
	 * @return null
	 */
	public function onSaveProfile(Event $event)
	{
		craft()->deprecator->log('UsersService::onSaveProfile()', 'The users.onSaveProfile event has been deprecated. Use users.onSaveUser instead.');
		$this->raiseEvent('onSaveProfile', $event);
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

		return craft()->email->sendEmailByKey($user, 'account_activation', array(
			'link' => TemplateHelper::getRaw($url),
		));
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

		return craft()->email->sendEmailByKey($user, 'verify_new_email', array(
			'link' => TemplateHelper::getRaw($url),
		));
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

		return craft()->email->sendEmailByKey($user, 'forgot_password', array(
			'link' => TemplateHelper::getRaw($url),
		));
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
		return $this->_getUserUrl($user, 'verifyEmail');
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
		return $this->_getUserUrl($user, 'setPassword');
	}

	/**
	 * Crops and saves a user’s photo.
	 *
	 * @param string    $fileName The name of the file.
	 * @param BaseImage $image    The image.
	 * @param UserModel $user     The user.
	 *
	 * @throws \Exception
	 * @return bool Whether the photo was saved successfully.
	 */
	public function saveUserPhoto($fileName, BaseImage $image, UserModel $user)
	{
		$userName = AssetsHelper::cleanAssetName($user->username, false, true);
		$userPhotoFolder = craft()->path->getUserPhotosPath().$userName.'/';
		$targetFolder = $userPhotoFolder.'original/';

		IOHelper::ensureFolderExists($userPhotoFolder);
		IOHelper::ensureFolderExists($targetFolder);

		$fileName = AssetsHelper::cleanAssetName($fileName);
		$targetPath = $targetFolder.$fileName;

		$result = $image->saveAs($targetPath);

		if ($result)
		{
			IOHelper::changePermissions($targetPath, craft()->config->get('defaultFilePermissions'));
			$record = UserRecord::model()->findById($user->id);
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
		$username = AssetsHelper::cleanAssetName($user->username, false);
		$folder = craft()->path->getUserPhotosPath().$username;

		if (IOHelper::folderExists($folder))
		{
			IOHelper::deleteFolder($folder);
		}

		$record = UserRecord::model()->findById($user->id);
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
	 * Handles a successful login for a user.
	 *
	 * @param UserModel $user         The user.
	 * @param string    $sessionToken The session token.
	 *
	 * @return string The session’s UID.
	 * @deprecated Deprecated in 2.3. Use {@link UsersService::updateUserLoginInfo() `craft()->users->updateUserLoginInfo()`}
	 *             and {@link UserSessionService::storeSessionToken() `craft()->userSession->storeSessionToken()`} instead.
	 */
	public function handleSuccessfulLogin(UserModel $user, $sessionToken)
	{
		$this->updateUserLoginInfo($user);

		return craft()->userSession->storeSessionToken($user, $sessionToken);
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
		$userRecord->lastLoginAttemptIPAddress = craft()->request->getUserHostAddress();
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
		$locked = false;

		$userRecord->lastInvalidLoginDate = $user->lastInvalidLoginDate = $currentTime;
		$userRecord->lastLoginAttemptIPAddress = craft()->request->getUserHostAddress();

		$maxInvalidLogins = craft()->config->get('maxInvalidLogins');

		if ($maxInvalidLogins)
		{
			if ($this->_isUserInsideInvalidLoginWindow($userRecord))
			{
				$userRecord->invalidLoginCount++;

				// Was that one bad password too many?
				if ($userRecord->invalidLoginCount >= $maxInvalidLogins)
				{
					$userRecord->locked = true;
					$user->locked = true;
					$userRecord->invalidLoginCount = null;
					$userRecord->invalidLoginWindowStart = null;
					$userRecord->lockoutDate = $user->lockoutDate = $currentTime;
					$locked = true;
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

		$saveSuccess = $userRecord->save();

		if ($locked)
		{
			// Fire an 'onLockUser' event
			$this->onLockUser(new Event($this, array(
				'user' => $user
			)));
		}

		return $saveSuccess;
	}

	/**
	 * Activates a user, bypassing email verification.
	 *
	 * @param UserModel $user The user.
	 *
	 * @throws \CDbException
	 * @throws \Exception
	 * @return bool Whether the user was activated successfully.
	 */
	public function activateUser(UserModel $user)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			// Fire an 'onBeforeActivateUser' event
			$event = new Event($this, array(
				'user' => $user,
			));

			$this->onBeforeActivateUser($event);

			// Is the event is giving us the go-ahead?
			if ($event->performAction)
			{
				$userRecord = $this->_getUserRecordById($user->id);

				$userRecord->setActive();
				$user->setActive();
				$userRecord->verificationCode = null;
				$userRecord->verificationCodeIssuedDate = null;
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
			// Fire an 'onActivateUser' event
			$this->onActivateUser(new Event($this, array(
				'user' => $user
			)));
		}

		return $success;
	}

	/**
	 * If 'unverifiedEmail' is set on the UserModel, then this method will transfer it to the official email property
	 * and clear the unverified one.
	 *
	 * @param UserModel $user
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function verifyEmailForUser(UserModel $user)
	{
		if ($user->unverifiedEmail)
		{
			$userRecord = $this->_getUserRecordById($user->id);
			$oldEmail = $userRecord->email;
			$userRecord->email = $user->unverifiedEmail;

			if (craft()->config->get('useEmailAsUsername'))
			{
				$userRecord->username = $user->unverifiedEmail;

				$oldProfilePhotoPath = craft()->path->getUserPhotosPath().AssetsHelper::cleanAssetName($oldEmail, false, true);
				$newProfilePhotoPath = craft()->path->getUserPhotosPath().AssetsHelper::cleanAssetName($user->unverifiedEmail, false, true);

				// Update the user profile photo folder name, if it exists.
				if (IOHelper::folderExists($oldProfilePhotoPath))
				{
					IOHelper::rename($oldProfilePhotoPath, $newProfilePhotoPath);
				}
			}

			$userRecord->unverifiedEmail = null;

			if (!$userRecord->save())
			{
				$user->addErrors($userRecord->getErrors());
				return false;
			}

			// If the user status is pending, let's activate them.
			if ($userRecord->pending == true)
			{
				$this->activateUser($user);
			}
		}

		return true;
	}

	/**
	 * Unlocks a user, bypassing the cooldown phase.
	 *
	 * @param UserModel $user The user.
	 *
	 * @throws \CDbException
	 * @throws \Exception
	 * @return bool Whether the user was unlocked successfully.
	 */
	public function unlockUser(UserModel $user)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			// Fire an 'onBeforeUnlockUser' event
			$event = new Event($this, array(
				'user'      => $user,
			));

			$this->onBeforeUnlockUser($event);

			// Is the event is giving us the go-ahead?
			if ($event->performAction)
			{
				$userRecord = $this->_getUserRecordById($user->id);

				$userRecord->locked = false;
				$user->locked = false;

				$userRecord->invalidLoginCount = $user->invalidLoginCount = null;
				$userRecord->invalidLoginWindowStart = null;
				$userRecord->lockoutDate = $user->lockoutDate = null;

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
			// Fire an 'onUnlockUser' event
			$this->onUnlockUser(new Event($this, array(
				'user' => $user
			)));
		}

		return $success;
	}

	/**
	 * Suspends a user.
	 *
	 * @param UserModel $user The user.
	 *
	 * @throws \CDbException
	 * @throws \Exception
	 * @return bool Whether the user was suspended successfully.
	 */
	public function suspendUser(UserModel $user)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			// Fire an 'onBeforeSuspendUser' event
			$event = new Event($this, array(
				'user'      => $user,
			));

			$this->onBeforeSuspendUser($event);

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
			// Fire an 'onSuspendUser' event
			$this->onSuspendUser(new Event($this, array(
				'user' => $user
			)));
		}

		return $success;
	}

	/**
	 * Unsuspends a user.
	 *
	 * @param UserModel $user The user.
	 *
	 * @throws \CDbException
	 * @throws \Exception
	 * @return bool Whether the user was unsuspended successfully.
	 */
	public function unsuspendUser(UserModel $user)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			// Fire an 'onBeforeUnsuspendUser' event
			$event = new Event($this, array(
				'user'      => $user,
			));

			$this->onBeforeUnsuspendUser($event);

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
			// Fire an 'onUnsuspendUser' event
			$this->onUnsuspendUser(new Event($this, array(
				'user' => $user
			)));
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

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			// Fire an 'onBeforeDeleteUser' event
			$event = new Event($this, array(
				'user'              => $user,
				'transferContentTo' => $transferContentTo
			));

			$this->onBeforeDeleteUser($event);

			// Is the event is giving us the go-ahead?
			if ($event->performAction)
			{
				// Get the entry IDs that belong to this user
				$entryIds = craft()->db->createCommand()
					->select('id')
					->from('entries')
					->where(array('authorId' => $user->id))
					->queryColumn();

				// Should we transfer the content to a new user?
				if ($transferContentTo)
				{
					// Delete the template caches for any entries authored by this user
					craft()->templateCache->deleteCachesByElementId($entryIds);

					// Update the entry/version/draft tables to point to the new user
					$userRefs = array(
						'entries' => 'authorId',
						'entrydrafts' => 'creatorId',
						'entryversions' => 'creatorId',
					);

					foreach ($userRefs as $table => $column)
					{
						craft()->db->createCommand()->update($table, array(
							$column => $transferContentTo->id
						), array(
							$column => $user->id
						));
					}
				}
				else
				{
					// Delete the entries
					craft()->elements->deleteElementById($entryIds);
				}

				// Delete the user
				$success = craft()->elements->deleteElementById($user->id);

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
			// Fire an 'onDeleteUser' event
			$this->onDeleteUser(new Event($this, array(
				'user'              => $user,
				'transferContentTo' => $transferContentTo
			)));
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

		$affectedRows = craft()->db->createCommand()->insertOrUpdate('shunnedmessages', array(
			'userId'  => $userId,
			'message' => $message
		), array(
			'expiryDate' => $expiryDate
		));

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
		$affectedRows = craft()->db->createCommand()->delete('shunnedmessages', array(
			'userId'  => $userId,
			'message' => $message
		));

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
		$row = craft()->db->createCommand()
			->select('id')
			->from('shunnedmessages')
			->where(array('and',
				'userId = :userId',
				'message = :message',
				array('or', 'expiryDate IS NULL', 'expiryDate > :now')
			), array(
				':userId'  => $userId,
				':message' => $message,
				':now'     => DateTimeHelper::formatTimeForDb()
			))
			->queryRow(false);

		return (bool) $row;
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
	 * Validates a given password against a hash.
	 *
	 * @param string $hash     The hashed password.
	 * @param string $password The submitted password.
	 *
	 * @return bool Whether the submitted password matches the hashed password.
	 */
	public function validatePassword($hash, $password)
	{
		if (craft()->security->checkPassword($password, $hash))
		{
			return true;
		}

		return false;
	}

	/**
	 * Deletes any pending users that have shown zero sense of urgency and are just taking up space.
	 *
	 * This method will check the
	 * [purgePendingUsersDuration](http://craftcms.com/docs/config-settings#purgePendingUsersDuration) config
	 * setting, and if it is set to a valid duration, it will delete any user accounts that were created that duration
	 * ago, and have still not activated their account.
	 *
	 * @return null
	 */
	public function purgeExpiredPendingUsers()
	{
		if (($duration = craft()->config->get('purgePendingUsersDuration')) !== false)
		{
			$interval = new DateInterval($duration);
			$expire = DateTimeHelper::currentUTCDateTime();
			$pastTimeStamp = $expire->sub($interval)->getTimestamp();
			$pastTime = DateTimeHelper::formatTimeForDb($pastTimeStamp);

			$userIds = craft()->db->createCommand()->select('id')
				->from('users')
				->where('pending=1 AND verificationCodeIssuedDate < :pastTime', array(':pastTime' => $pastTime))
				->queryColumn();

			if ($userIds)
			{
				foreach ($userIds as $userId)
				{
					$user = $this->getUserById($userId);
					$this->deleteUser($user);

					Craft::log('Just deleted pending userId '.$userId.' ('.$user->username.'), because the were more than '.$duration.' old', LogLevel::Info, true);
				}
			}
		}
	}

	// Events
	// -------------------------------------------------------------------------

	/**
	 * Fires an 'onBeforeSaveUser' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeSaveUser(Event $event)
	{
		$this->raiseEvent('onBeforeSaveUser', $event);
	}

	/**
	 * Fires an 'onSaveUser' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onSaveUser(Event $event)
	{
		$this->raiseEvent('onSaveUser', $event);
	}

	/**
	 * Fires an 'onBeforeVerifyUser' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeVerifyUser(Event $event)
	{
		$this->raiseEvent('onBeforeVerifyUser', $event);
	}

	/**
	 * Fires an 'onVerifyUser' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onVerifyUser(Event $event)
	{
		$this->raiseEvent('onVerifyUser', $event);
	}

	/**
	 * Fires an 'onBeforeActivateUser' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeActivateUser(Event $event)
	{
		$this->raiseEvent('onBeforeActivateUser', $event);
	}

	/**
	 * Fires an 'onActivateUser' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onActivateUser(Event $event)
	{
		$this->raiseEvent('onActivateUser', $event);
	}

	/**
	 * Fires an 'onBeforeUnlockUser' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeUnlockUser(Event $event)
	{
		$this->raiseEvent('onBeforeUnlockUser', $event);
	}

	/**
	 * Fires an 'onUnlockUser' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onUnlockUser(Event $event)
	{
		$this->raiseEvent('onUnlockUser', $event);
	}

	/**
	 * Fires an 'onLockUser' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onLockUser(Event $event)
	{
		$this->raiseEvent('onLockUser', $event);
	}

	/**
	 * Fires an 'onBeforeSuspendUser' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeSuspendUser(Event $event)
	{
		$this->raiseEvent('onBeforeSuspendUser', $event);
	}

	/**
	 * Fires an 'onSuspendUser' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onSuspendUser(Event $event)
	{
		$this->raiseEvent('onSuspendUser', $event);
	}

	/**
	 * Fires an 'onBeforeUnsuspendUser' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeUnsuspendUser(Event $event)
	{
		$this->raiseEvent('onBeforeUnsuspendUser', $event);
	}

	/**
	 * Fires an 'onUnsuspendUser' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onUnsuspendUser(Event $event)
	{
		$this->raiseEvent('onUnsuspendUser', $event);
	}

	/**
	 * Fires an 'onBeforeDeleteUser' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeDeleteUser(Event $event)
	{
		$this->raiseEvent('onBeforeDeleteUser', $event);
	}

	/**
	 * Fires an 'onDeleteUser' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onDeleteUser(Event $event)
	{
		$this->raiseEvent('onDeleteUser', $event);
	}

	// Deprecated Methods
	// -------------------------------------------------------------------------

	/**
	 * Sends a password reset email.
	 *
	 * @param UserModel $user The user to send the forgot password email to.
	 *
	 * @deprecated Deprecated in 2.3. Use {@link sendPasswordResetEmail()} instead.
	 * @return bool Whether the email was sent successfully.
	 */
	public function sendForgotPasswordEmail(UserModel $user)
	{
		// TODO: Add a deprecation log in Craft 3.0
		return $this->sendPasswordResetEmail($user);
	}

	/**
	 * Fires an 'onBeforeSetPassword' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeSetPassword(Event $event)
	{
		$this->raiseEvent('onBeforeSetPassword', $event);
	}

	/**
	 * Fires an 'onSetPassword' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onSetPassword(Event $event)
	{
		$this->raiseEvent('onSetPassword', $event);
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
		$userRecord = UserRecord::model()->findById($userId);

		if (!$userRecord)
		{
			throw new Exception(Craft::t('No user exists with the ID “{id}”.', array('id' => $userId)));
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
		$unhashedCode = craft()->security->generateRandomString(32);
		$hashedCode = craft()->security->hashPassword($unhashedCode);
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
			$duration = new DateInterval(craft()->config->get('invalidLoginWindowDuration'));
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
	 *                                                {@link UserModel::passwordResetRequired passwordResetRequired}
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
		if (!$user->id && craft()->systemSettings->getSetting('users', 'allowPublicRegistration'))
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
				if (craft()->security->checkPassword($user->newPassword, $userRecord->password))
				{
					$user->addErrors(array(
						$passwordErrorField => Craft::t('That password is the same as your old password. Please choose a new one.'),
					));
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
				// Fire an 'onBeforeSetPassword' event
				$event = new Event($this, array(
					'password' => $user->newPassword,
					'user'     => $user
				));

				$this->onBeforeSetPassword($event);

				// Is the event is giving us the go-ahead?
				$validates = $event->performAction;
			}
		}

		if ($validates)
		{
			$hash = craft()->security->hashPassword($user->newPassword);

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
			$user->addErrors(array(
				$passwordErrorField => $passwordModel->getErrors('password')
			));

			$success = false;
		}

		if ($success)
		{
			// Fire an 'onSetPassword' event
			$this->onSetPassword(new Event($this, array(
				'user' => $user
			)));

		}

		return $success;
	}

	/**
	 * Sets a new verification code on a user, and returns their new verification URL
	 *
	 * @param UserModel $user   The user that should get the new Password Reset URL
	 * @param string    $action The UsersController action that the URL should point to
	 *
	 * @return string The new Password Reset URL.
	 * @see getPasswordResetUrl()
	 * @see getEmailVerifyUrl()
	 */
	private function _getUserUrl(UserModel $user, $action)
	{
		$userRecord = $this->_getUserRecordById($user->id);
		$unhashedVerificationCode = $this->_setVerificationCodeOnUserRecord($userRecord);
		$userRecord->save();

		$path = craft()->config->get('actionTrigger').'/users/'.$action;
		$params = array(
			'code' => $unhashedVerificationCode,
			'id' => $userRecord->uid
		);

		$scheme = UrlHelper::getProtocolForTokenizedUrl();

		if ($user->can('accessCp'))
		{
			// Only use getCpUrl() if the base CP URL has been explicitly set,
			// so UrlHelper won't use HTTP_HOST
			if (craft()->config->get('baseCpUrl'))
			{
				return UrlHelper::getCpUrl($path, $params, $scheme);
			}

			$path = craft()->config->get('cpTrigger').'/'.$path;
		}

		$locale = $user->preferredLocale ?: craft()->i18n->getPrimarySiteLocaleId();
		return UrlHelper::getSiteUrl($path, $params, $scheme, $locale);
	}
}
