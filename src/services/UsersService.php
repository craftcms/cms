<?php
namespace Craft;

/**
 * UsersService provides APIs for managing users.
 *
 * An instance of UsersService is globally accessible in Craft via {@link WebApp::users `craft()->users`}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
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
				throw new Exception(Craft::t('No user exists with the ID “{id}”', array('id' => $user->id)));
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
		$userRecord->status                = $user->status;
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
			$this->_setPasswordOnUserRecord($user, $userRecord, false);
		}

		if ($user->hasErrors())
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// If we're going through account verification, in whatever form
			if ($user->unverifiedEmail)
			{
				$unhashedVerificationCode = $this->_setVerificationCodeOnUserRecord($userRecord);
			}

			// Set a default status of pending, if one wasn't supplied.
			if (!$user->status)
			{
				$user->status = UserStatus::Pending;
			}

			// Fire an 'onBeforeSaveUser' event
			$this->onBeforeSaveUser(new Event($this, array(
				'user'      => $user,
				'isNewUser' => $isNewUser
			)));

			if (craft()->elements->saveElement($user, false))
			{
				// Now that we have an element ID, save it on the other stuff
				if ($isNewUser)
				{
					$userRecord->id = $user->id;
				}

				$userRecord->save(false);

				if ($user->unverifiedEmail)
				{
					// Temporarily set the unverified email on the UserModel so the verification email goes to the
					// right place
					$originalEmail = $user->email;
					$user->email = $user->unverifiedEmail;

					try
					{
						craft()->email->sendEmailByKey($user, $isNewUser ? 'account_activation' : 'verify_new_email', array(
							'link' => TemplateHelper::getRaw(craft()->config->getActivateAccountPath($unhashedVerificationCode, $userRecord->uid)),
						));
					}
					catch (\phpmailerException $e)
					{
						craft()->userSession->setError(Craft::t('User saved, but couldn’t send verification email. Check your email settings.'));
					}

					$user->email = $originalEmail;
				}

				if (!$isNewUser)
				{
					// Has the username changed?
					if ($user->username != $oldUsername)
					{
						// Rename the user's photo directory
						$oldFolder = craft()->path->getUserPhotosPath().$oldUsername;
						$newFolder = craft()->path->getUserPhotosPath().$user->username;

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

				if ($transaction !== null)
				{
					$transaction->commit();
				}
			}
			else
			{
				return false;
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

		// If we've made it here, everything has been successful so far.

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

		return true;
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
		$userRecord = $this->_getUserRecordById($user->id);
		$unhashedVerificationCode = $this->_setVerificationCodeOnUserRecord($userRecord);
		$userRecord->save();

		return craft()->email->sendEmailByKey($user, 'account_activation', array(
			'link' => TemplateHelper::getRaw(craft()->config->getActivateAccountPath($unhashedVerificationCode, $userRecord->uid)),
		));
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
		$userPhotoFolder = craft()->path->getUserPhotosPath().$userName.'/';
		$targetFolder = $userPhotoFolder.'original/';

		IOHelper::ensureFolderExists($userPhotoFolder);
		IOHelper::ensureFolderExists($targetFolder);

		$targetPath = $targetFolder.AssetsHelper::cleanAssetName($fileName);

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
		$folder = craft()->path->getUserPhotosPath().$user->username;

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
	 * Sends a “Forgot Password” email to a given user.
	 *
	 * @param UserModel $user The user.
	 *
	 * @return bool Whether the email was sent successfully.
	 */
	public function sendForgotPasswordEmail(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);
		$unhashedVerificationCode = $this->_setVerificationCodeOnUserRecord($userRecord);
		$userRecord->save();

		$url = UrlHelper::getActionUrl('users/setpassword', array('code' => $unhashedVerificationCode, 'id' => $userRecord->uid), craft()->request->isSecureConnection() ? 'https' : 'http');
		return craft()->email->sendEmailByKey($user, 'forgot_password', array(
			'link' => TemplateHelper::getRaw($url),
		));
	}

	/**
	 * Changes a user’s password.
	 *
	 * @param UserModel $user The user.
	 *
	 * @return bool Whether the user’s new password was saved successfully.
	 */
	public function changePassword(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);

		if ($this->_setPasswordOnUserRecord($user, $userRecord))
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
	 */
	public function handleSuccessfulLogin(UserModel $user, $sessionToken)
	{
		$userRecord = $this->_getUserRecordById($user->id);

		$userRecord->lastLoginDate = $user->lastLoginDate = DateTimeHelper::currentUTCDateTime();
		$userRecord->lastLoginAttemptIPAddress = craft()->request->getUserHostAddress();
		$userRecord->invalidLoginWindowStart = null;
		$userRecord->invalidLoginCount = $user->invalidLoginCount = null;
		$userRecord->verificationCode = null;
		$userRecord->verificationCodeIssuedDate = null;

		$sessionRecord = new SessionRecord();
		$sessionRecord->userId = $user->id;
		$sessionRecord->token = $sessionToken;

		$userRecord->save();
		$sessionRecord->save();

		return $sessionRecord->uid;
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
		$userRecord->lastLoginAttemptIPAddress = craft()->request->getUserHostAddress();

		$maxInvalidLogins = craft()->config->get('maxInvalidLogins');

		if ($maxInvalidLogins)
		{
			if ($this->_isUserInsideInvalidLoginWindow($userRecord))
			{
				$userRecord->invalidLoginCount++;

				// Was that one bad password too many?
				if ($userRecord->invalidLoginCount > $maxInvalidLogins)
				{
					$userRecord->status = $user->status = UserStatus::Locked;
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
	 * @return bool Whether the user was activated successfully.
	 */
	public function activateUser(UserModel $user)
	{
		// Fire an 'onBeforeActivateUser' event
		$this->onBeforeActivateUser(new Event($this, array(
			'user' => $user
		)));

		$userRecord = $this->_getUserRecordById($user->id);

		$userRecord->status = $user->status = UserStatus::Active;
		$userRecord->verificationCode = null;
		$userRecord->verificationCodeIssuedDate = null;
		$userRecord->lockoutDate = null;

		// If they have an unverified email address, now is the time to set it to their primary email address
		if ($user->unverifiedEmail)
		{
			$userRecord->email = $user->unverifiedEmail;

			if (craft()->config->get('useEmailAsUsername'))
			{
				$userRecord->username = $user->unverifiedEmail;
			}

			$userRecord->unverifiedEmail = null;
		}

		if ($userRecord->save())
		{
			// Fire an 'onActivateUser' event
			$this->onActivateUser(new Event($this, array(
				'user' => $user
			)));

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Unlocks a user, bypassing the cooldown phase.
	 *
	 * @param UserModel $user The user.
	 *
	 * @return bool Whether the user was unlocked successfully.
	 */
	public function unlockUser(UserModel $user)
	{
		// Fire an 'onBeforeUnlockUser' event
		$this->onBeforeUnlockUser(new Event($this, array(
			'user' => $user
		)));

		$userRecord = $this->_getUserRecordById($user->id);

		$userRecord->status = $user->status = UserStatus::Active;
		$userRecord->invalidLoginCount = $user->invalidLoginCount = null;
		$userRecord->invalidLoginWindowStart = null;

		if ($userRecord->save())
		{
			// Fire an 'onUnlockUser' event
			$this->onUnlockUser(new Event($this, array(
				'user' => $user
			)));

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Suspends a user.
	 *
	 * @param UserModel $user The user.
	 *
	 * @return bool Whether the user was suspended successfully.
	 */
	public function suspendUser(UserModel $user)
	{
		// Fire an 'onBeforeSuspendUser' event
		$this->onBeforeSuspendUser(new Event($this, array(
			'user' => $user
		)));

		$userRecord = $this->_getUserRecordById($user->id);

		$userRecord->status = $user->status = UserStatus::Suspended;

		if ($userRecord->save())
		{
			// Fire an 'onSuspendUser' event
			$this->onSuspendUser(new Event($this, array(
				'user' => $user
			)));

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Unsuspends a user.
	 *
	 * @param UserModel $user The user.
	 *
	 * @return bool Whether the user was unsuspended successfully.
	 */
	public function unsuspendUser(UserModel $user)
	{
		// Fire an 'onBeforeUnsuspendUser' event
		$this->onBeforeUnsuspendUser(new Event($this, array(
			'user' => $user
		)));

		$userRecord = $this->_getUserRecordById($user->id);

		$userRecord->status = $user->status = UserStatus::Active;

		if ($userRecord->save())
		{
			// Fire an 'onUnsuspendUser' event
			$this->onUnsuspendUser(new Event($this, array(
				'user' => $user
			)));

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Deletes a user.
	 *
	 * @param UserModel $user The user.
	 *
	 * @throws \Exception
	 * @return bool Whether the user was deleted successfully.
	 */
	public function deleteUser(UserModel $user)
	{
		if (!$user->id)
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Fire an 'onBeforeDeleteUser' event
			$this->onBeforeDeleteUser(new Event($this, array(
				'user' => $user
			)));

			// Grab the entry IDs that were authored by this user so we can delete them too.
			$criteria = craft()->elements->getCriteria(ElementType::Entry);
			$criteria->authorId = $user->id;
			$criteria->limit = null;
			$entries = $criteria->find();

			if ($entries)
			{
				craft()->entries->deleteEntry($entries);
			}

			// Delete the user
			$success = craft()->elements->deleteElementById($user->id);

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
				'user' => $user
			)));

			return true;
		}
		else
		{
			return false;
		}
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
	 * [purgePendingUsersDuration](http://buildwithcraft.com/docs/config-settings#purgePendingUsersDuration) config
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

			$ids = craft()->db->createCommand()->select('id')
				->from('users')
				->where('status = :status AND verificationCodeIssuedDate < :pastTime', array('status' => 'pending', 'pastTime' => $pastTime))
				->queryColumn();

			$affectedRows = craft()->db->createCommand()->delete('elements', array('in', 'id', $ids));

			if ($affectedRows > 0)
			{
				Craft::log('Just deleted '.$affectedRows.' pending users from the users table, because the were more than '.$duration.' old', LogLevel::Info, true);
			}
		}
	}

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
			throw new Exception(Craft::t('No user exists with the ID “{id}”', array('id' => $userId)));
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
	 *
	 * @return bool
	 */
	private function _setPasswordOnUserRecord(UserModel $user, UserRecord $userRecord, $updatePasswordResetRequired = true)
	{
		// Validate the password first
		$passwordModel = new PasswordModel();
		$passwordModel->password = $user->newPassword;

		if ($passwordModel->validate())
		{
			$hash = craft()->security->hashPassword($user->newPassword);

			$userRecord->password = $user->password = $hash;

			if (!$user->unverifiedEmail)
			{
				$userRecord->status = $user->status = UserStatus::Active;
			}

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

			return true;
		}
		else
		{
			// If it's a new user AND we allow public registration, set it on the 'password' field and not 'newpassword'.
			if (!$user->id && craft()->systemSettings->getSetting('users', 'allowPublicRegistration'))
			{
				$user->addErrors(array(
					'password' => $passwordModel->getErrors('password')
				));
			}
			else
			{
				$user->addErrors(array(
					'newPassword' => $passwordModel->getErrors('password')
				));
			}

			return false;
		}
	}
}
