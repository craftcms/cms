<?php
namespace Craft;

/**
 *
 */
class UsersService extends BaseApplicationComponent
{
	private $_usersById;

	/**
	 * Gets a user by their ID.
	 *
	 * @param $userId
	 * @return UserModel|null
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
	 * Gets a user by their username or email.
	 *
	 * @param string $usernameOrEmail
	 * @return UserModel|null
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
	}

	/**
	 * Gets a user by a verification code and their uid.
	 *
	 * @param        $code
	 * @param        $uid
	 * @return UserModel|null
	 */
	public function getUserByVerificationCodeAndUid($code, $uid)
	{
		$userRecord = UserRecord::model()->findByAttributes(array(
			'uid' => $uid
		));

		if ($userRecord && $userRecord->verificationCodeIssuedDate)
		{
			$user = UserModel::populateModel($userRecord);

			// Fire an 'onBeforeVerifyUser' event
			$this->onBeforeVerifyUser(new Event($this, array(
				'user' => $user
			)));

			$minCodeIssueDate = DateTimeHelper::currentUTCDateTime();
			$duration = new DateInterval(craft()->config->get('verificationCodeDuration'));
			$minCodeIssueDate->sub($duration);

			if (
				$userRecord->verificationCodeIssuedDate > $minCodeIssueDate &&
				craft()->security->checkPassword($code, $userRecord->verificationCode)
			)
			{
				return $user;
			}
			else
			{
				Craft::log('Found a user with UID:'.$uid.', but the verification code given: '.$code.' has either expired or does not match the hash in the database.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Could not find a user with UID:'.$uid.'.', LogLevel::Warning);
		}

		return null;
	}

	/**
	 * Returns the "Client" account if they're running Craft Client.
	 *
	 * @return UserModel|null
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
	 * Saves a user, or registers a new one.
	 *
	 * @param  UserModel $user
	 * @throws \Exception
	 * @return bool
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
			$this->_setPasswordOnUserRecord($user, $userRecord);
		}

		if (!$user->hasErrors())
		{
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
						// Temporarily set the unverified email on the UserModel so the verification email goes to the right place
						$originalEmail = $user->email;
						$user->email = $user->unverifiedEmail;

						try
						{
							craft()->email->sendEmailByKey($user, 'account_activation', array(
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
			}
			catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				throw $e;
			}
		}

		return false;
	}

	/**
	 * Saves a user's profile.
	 *
	 * @param UserModel $user
	 * @return bool
	 * @deprecated Deprecated in 2.0.
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
	 * @deprecated Deprecated in 2.0.
	 */
	public function onSaveProfile(Event $event)
	{
		craft()->deprecator->log('UsersService::onSaveProfile()', 'The users.onSaveProfile event has been deprecated. Use users.onSaveUser instead.');
		$this->raiseEvent('onSaveProfile', $event);
	}

	/**
	 * Sends an activation email
	 */
	public function sendActivationEmail(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);
		$unhashedVerificationCode = $this->_setVerificationCodeOnUserRecord($userRecord);
		$userRecord->save();

		return craft()->email->sendEmailByKey($user, 'account_activation', array(
			'link' => new \Twig_Markup(craft()->config->getActivateAccountPath($unhashedVerificationCode, $userRecord->uid), craft()->templates->getTwig()->getCharset()),
		));
	}

	/**
	 * Crop and save a user's photo by coordinates for a given user model.
	 *
	 * @param $fileName
	 * @param Image $image
	 * @param UserModel $user
	 * @return bool
	 * @throws \Exception
	 */
	public function saveUserPhoto($fileName, Image $image, UserModel $user)
	{
		$userPhotoFolder = craft()->path->getUserPhotosPath().$user->username.'/';
		$targetFolder = $userPhotoFolder.'original/';

		IOHelper::ensureFolderExists($userPhotoFolder);
		IOHelper::ensureFolderExists($targetFolder);

		$targetPath = $targetFolder . $fileName;

		$result = $image->saveAs($targetPath);

		if ($result)
		{
			IOHelper::changePermissions($targetPath, IOHelper::getWritableFilePermissions());
			$record = UserRecord::model()->findById($user->id);
			$record->photo = $fileName;
			$record->save();

			$user->photo = $fileName;

			return true;
		}

		return false;
	}

	/**
	 * Delete a user's photo.
	 *
	 * @param UserModel $user
	 * @return void
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
	 * Sends a "forgot password" email.
	 *
	 * @param UserModel $user
	 * @return bool
	 */
	public function sendForgotPasswordEmail(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);
		$unhashedVerificationCode = $this->_setVerificationCodeOnUserRecord($userRecord);
		$userRecord->save();

		$url = UrlHelper::getActionUrl('users/setpassword', array('code' => $unhashedVerificationCode, 'id' => $userRecord->uid), craft()->request->isSecureConnection() ? 'https' : 'http');
		return craft()->email->sendEmailByKey($user, 'forgot_password', array(
			'link' => new \Twig_Markup($url, craft()->templates->getTwig()->getCharset()),
		));
	}

	/**
	 * Changes a user's password.
	 *
	 * @param UserModel $user
	 * @return bool
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
	 * @param UserModel $user
	 * @param           $sessionToken
	 * @return bool
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
	 * @param UserModel $user
	 * @return bool
	 */
	public function handleInvalidLogin(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);
		$currentTime = DateTimeHelper::currentUTCDateTime();

		$userRecord->lastInvalidLoginDate = $user->lastInvalidLoginDate = $currentTime;
		$userRecord->lastLoginAttemptIPAddress = craft()->request->getUserHostAddress();

		if ($this->_isUserInsideInvalidLoginWindow($userRecord))
		{
			$userRecord->invalidLoginCount++;

			// Was that one bad password too many?
			if ($userRecord->invalidLoginCount >= craft()->config->get('maxInvalidLogins'))
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

		return $userRecord->save();
	}

	/**
	 * Activates a user, bypassing email verification.
	 *
	 * @param UserModel $user
	 * @return bool
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
	 * @param UserModel $user
	 * @return bool
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
	 * @param UserModel $user
	 * @return bool
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
	 * @param UserModel $user
	 * @return bool
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
	 * @param UserModel $user
	 * @throws \Exception
	 * @return bool
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
	 * @param int      $userId
	 * @param string   $message
	 * @param DateTime $expiryDate
	 * @return bool
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
	 * Unshuns a message for a user.
	 *
	 * @param int      $userId
	 * @param string   $message
	 * @return bool
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
	 * @param int      $userId
	 * @param string   $message
	 * @return bool
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
	 * @param UserModel $user
	 * @return string
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
	 * @param $hash
	 * @param $password
	 * @return bool
	 */
	public function validatePassword($hash, $password)
	{
		if (craft()->security->checkPassword($password, $hash))
		{
			return true;
		}

		return false;
	}

	// Events

	/**
	 * Fires an 'onBeforeSaveUser' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeSaveUser(Event $event)
	{
		$this->raiseEvent('onBeforeSaveUser', $event);
	}

	/**
	 * Fires an 'onSaveUser' event.
	 *
	 * @param Event $event
	 */
	public function onSaveUser(Event $event)
	{
		$this->raiseEvent('onSaveUser', $event);
	}

	/**
	 * Fires an 'onBeforeVerifyUser' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeVerifyUser(Event $event)
	{
		$this->raiseEvent('onBeforeVerifyUser', $event);
	}

	/**
	 * Fires an 'onBeforeActivateUser' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeActivateUser(Event $event)
	{
		$this->raiseEvent('onBeforeActivateUser', $event);
	}

	/**
	 * Fires an 'onActivateUser' event.
	 *
	 * @param Event $event
	 */
	public function onActivateUser(Event $event)
	{
		$this->raiseEvent('onActivateUser', $event);
	}

	/**
	 * Fires an 'onBeforeUnlockUser' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeUnlockUser(Event $event)
	{
		$this->raiseEvent('onBeforeUnlockUser', $event);
	}

	/**
	 * Fires an 'onUnlockUser' event.
	 *
	 * @param Event $event
	 */
	public function onUnlockUser(Event $event)
	{
		$this->raiseEvent('onUnlockUser', $event);
	}

	/**
	 * Fires an 'onBeforeSuspendUser' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeSuspendUser(Event $event)
	{
		$this->raiseEvent('onBeforeSuspendUser', $event);
	}

	/**
	 * Fires an 'onSuspendUser' event.
	 *
	 * @param Event $event
	 */
	public function onSuspendUser(Event $event)
	{
		$this->raiseEvent('onSuspendUser', $event);
	}

	/**
	 * Fires an 'onBeforeUnsuspendUser' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeUnsuspendUser(Event $event)
	{
		$this->raiseEvent('onBeforeUnsuspendUser', $event);
	}

	/**
	 * Fires an 'onUnsuspendUser' event.
	 *
	 * @param Event $event
	 */
	public function onUnsuspendUser(Event $event)
	{
		$this->raiseEvent('onUnsuspendUser', $event);
	}

	/**
	 * Fires an 'onBeforeDeleteUser' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeDeleteUser(Event $event)
	{
		$this->raiseEvent('onBeforeDeleteUser', $event);
	}

	/**
	 * Fires an 'onDeleteUser' event.
	 *
	 * @param Event $event
	 */
	public function onDeleteUser(Event $event)
	{
		$this->raiseEvent('onDeleteUser', $event);
	}

	// Private stuff

	/**
	 * Gets a user record by its ID.
	 *
	 * @access private
	 * @param int $userId
	 * @return UserRecord
	 * @throws Exception
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
	 * @access private
	 * @param  UserRecord $userRecord
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
	 * @access private
	 * @param UserModel $user
	 * @param UserRecord $userRecord
	 * @return bool
	 */
	private function _setPasswordOnUserRecord(UserModel $user, UserRecord $userRecord)
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
			if ($user->id)
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
