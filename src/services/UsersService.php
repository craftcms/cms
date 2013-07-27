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
	 * @return UserModel
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
		$date = DateTimeHelper::currentUTCDateTime();
		$duration = new DateInterval(craft()->config->get('verificationCodeDuration'));
		$date->sub($duration);

		$userRecord = UserRecord::model()->find(
			'verificationCodeIssuedDate >:date AND uid=:uid',
			array(':date' => DateTimeHelper::formatTimeForDb($date->getTimestamp()), ':uid' => $uid)
		);

		if ($userRecord)
		{
			if (craft()->security->checkString($code, $userRecord->verificationCode))
			{
				return UserModel::populateModel($userRecord);
			}
			else
			{
				Craft::log('Found a with UID:'.$uid.', but the verification code given: '.$code.' does not match the hash in the database.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Could not find a user with UID:'.$uid.' that has a verification code that is not expired.', LogLevel::Warning);
		}

		return null;
	}

	/**
	 * Finds users.
	 *
	 * @param UserCriteria|null $criteria
	 * @return array
	 */
	public function findUsers(UserCriteria $criteria = null)
	{
		if (!$criteria)
		{
			$criteria = new UserCriteria();
		}

		$query = craft()->db->createCommand()
			->select('u.*')
			->from('users u');

		$this->_applyUserConditions($query, $criteria);

		if ($criteria->order)
		{
			$query->order($criteria->order);
		}

		if ($criteria->offset)
		{
			$query->offset($criteria->offset);
		}

		if ($criteria->limit)
		{
			$query->limit($criteria->limit);
		}

		$result = $query->queryAll();
		return UserModel::populateModels($result, $criteria->indexBy);
	}

	/**
	 * Finds a user.
	 *
	 * @param UserCriteria|null $criteria
	 * @return array
	 */
	public function findUser(UserCriteria $criteria = null)
	{
		if (!$criteria)
		{
			$criteria = new UserCriteria();
		}

		$query = craft()->db->createCommand()
			->select('u.*')
			->from('users u');

		$this->_applyUserConditions($query, $criteria);

		$result = $query->queryRow();

		if ($result)
		{
			return UserModel::populateModel($result);
		}
	}

	/**
	 * Gets the total number of users.
	 *
	 * @param array $criteria
	 * @return int
	 * @return int
	 */
	public function getTotalUsers($criteria = array())
	{
		if (!$criteria)
		{
			$criteria = new UserCriteria();
		}

		$query = craft()->db->createCommand()
			->select('count(u.id)')
			->from('users u');

		$this->_applyUserConditions($query, $criteria);

		return (int)$query->queryScalar();
	}

	/**
	 * Saves a user, or registers a new one.
	 *
	 * @param UserModel $user
	 * @throws Exception
	 * @return bool
	 */
	public function saveUser(UserModel $user)
	{
		if (($isNewUser = !$user->id) == false)
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
		$userRecord->email                 = $user->email;
		$userRecord->admin                 = $user->admin;
		$userRecord->passwordResetRequired = $user->passwordResetRequired;
		$userRecord->preferredLocale       = $user->preferredLocale;

		$userRecord->validate();
		$user->addErrors($userRecord->getErrors());

		// If newPassword is set at all, even to an empty string, validate & set it.
		if ($user->newPassword !== null)
		{
			$this->_setPasswordOnUserRecord($user, $userRecord);
		}

		if (!$user->hasErrors())
		{
			if ($user->verificationRequired)
			{
				$userRecord->status = $user->status = UserStatus::Pending;
				$unhashedVerificationCode = $this->_setVerificationCodeOnUserRecord($userRecord);
			}

			// Fire an 'onBeforeSaveUser' event
			$this->onBeforeSaveUser(new Event($this, array(
				'user'      => $user,
				'isNewUser' => $isNewUser
			)));

			if ($isNewUser)
			{
				// Create the entry record
				$elementRecord = new ElementRecord();
				$elementRecord->type = ElementType::User;
				$elementRecord->save();

				// Now that we have the entry ID, save it on everything else
				$user->id = $elementRecord->id;
				$userRecord->id = $elementRecord->id;
			}

			$userRecord->save(false);

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

			// Update the search index
			craft()->search->indexElementAttributes($user);

			if ($isNewUser && $user->verificationRequired)
			{
				craft()->templates->registerTwigAutoloader();

				craft()->email->sendEmailByKey($user, 'account_activation', array(
					'link' => new \Twig_Markup(craft()->config->getActivateAccountPath($unhashedVerificationCode, $userRecord->uid), craft()->templates->getTwig()->getCharset()),
				));
			}

			// Fire an 'onSaveUser' event
			$this->onSaveUser(new Event($this, array(
				'user'      => $user,
				'isNewUser' => $isNewUser
			)));

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Saves a user's profile.
	 *
	 * @param UserModel $user
	 * @return bool
	 */
	public function saveProfile(UserModel $user)
	{
		Craft::requirePackage(CraftPackage::Users);

		$fieldLayout = craft()->fields->getLayoutByType(ElementType::User);

		if (craft()->content->saveElementContent($user, $fieldLayout))
		{
			// Fire an 'onSaveProfile' event
			$this->onSaveProfile(new Event($this, array(
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
	 * Fires an 'onSaveProfile' event.
	 *
	 * @param Event $event
	 */
	public function onSaveProfile(Event $event)
	{
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

		craft()->templates->registerTwigAutoloader();

		return craft()->email->sendEmailByKey($user, 'account_activation', array(
			'link' => new \Twig_Markup(craft()->config->getActivateAccountPath($unhashedVerificationCode, $userRecord->uid), craft()->templates->getTwig()->getCharset()),
		));
	}

	/**
	 * Crop and save a user's photo by coordinates for a given user model.
	 *
	 * @param $source
	 * @param $x1
	 * @param $x2
	 * @param $y1
	 * @param $y2
	 * @param UserModel $user
	 * @return bool
	 * @throws \Exception
	 */
	public function cropAndSaveUserPhoto($source, $x1, $x2, $y1, $y2, UserModel $user)
	{
		$userPhotoFolder = craft()->path->getUserPhotosPath().$user->username.'/';
		$targetFolder = $userPhotoFolder.'original/';

		IOHelper::ensureFolderExists($userPhotoFolder);
		IOHelper::ensureFolderExists($targetFolder);

		$filename = pathinfo($source, PATHINFO_BASENAME);
		$targetPath = $targetFolder . $filename;


		$image = craft()->images->loadImage($source);
		$image->crop($x1, $x2, $y1, $y2);
		$result = $image->saveAs($targetPath);

		if ($result)
		{
			IOHelper::changePermissions($targetPath, IOHelper::getWritableFilePermissions());
			$record = UserRecord::model()->findById($user->id);
			$record->photo = $filename;
			$record->save();

			$user->photo = $filename;

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

		craft()->templates->registerTwigAutoloader();

		return craft()->email->sendEmailByKey($user, 'forgot_password', array(
			'link' => new \Twig_Markup(craft()->config->getSetPasswordPath($unhashedVerificationCode, $userRecord->uid), craft()->templates->getTwig()->getCharset()),
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
		$userRecord = $this->_getUserRecordById($user->id);

		$userRecord->status = $user->status = UserStatus::Active;
		$userRecord->verificationCode = null;
		$userRecord->verificationCodeIssuedDate = null;
		$userRecord->lockoutDate = null;

		return $userRecord->save();
	}

	/**
	 * Unlocks a user, bypassing the cooldown phase.
	 *
	 * @param UserModel $user
	 * @return bool
	 */
	public function unlockUser(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);

		$userRecord->status = $user->status = UserStatus::Active;
		$userRecord->invalidLoginCount = $user->invalidLoginCount = null;
		$userRecord->invalidLoginWindowStart = null;

		return $userRecord->save();
	}

	/**
	 * Suspends a user.
	 *
	 * @param UserModel $user
	 * @return bool
	 */
	public function suspendUser(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);

		$userRecord->status = $user->status = UserStatus::Suspended;

		return $userRecord->save();
	}

	/**
	 * Deletes a user.
	 *
	 * @param UserModel $user
	 * @return bool
	 */
	public function deleteUser(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);

		if ($userRecord)
		{
			// Fire an 'onBeforeDeleteUser' event
			$this->onBeforeDeleteUser(new Event($this, array(
				'user' => $user
			)));

			$success = $userRecord->delete();

			if ($success)
			{
				// Fire an 'onDeleteUser' event
				$this->onDeleteUser(new Event($this, array(
					'user' => $user
				)));

				return true;
			}
		}

		return false;
	}

	/**
	 * Unsuspends a user.
	 *
	 * @param UserModel $user
	 * @return bool
	 */
	public function unsuspendUser(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);

		$userRecord->status = $user->status = UserStatus::Active;

		return $userRecord->save();
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
		if (craft()->security->checkString($password, $hash))
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
	 * Applies WHERE conditions to a DbCommand query for users.
	 *
	 * @access private
	 * @param  DbCommand $query
	 * @param            $criteria
	 * @return void
	 */
	private function _applyUserConditions($query, $criteria)
	{
		$whereConditions = array();
		$whereParams = array();

		if ($criteria->id)
		{
			$whereConditions[] = DbHelper::parseParam('u.id', $criteria->id, $whereParams);
		}

		if ($criteria->groupId || $criteria->group)
		{
			$query->join('usergroups_users gu', 'gu.userId = u.id');

			if ($criteria->groupId)
			{
				$whereConditions[] = DbHelper::parseParam('gu.groupId', $criteria->groupId, $whereParams);
			}

			if ($criteria->group)
			{
				$query->join('usergroups g', 'g.id = gu.groupId');
				$whereConditions[] = DbHelper::parseParam('g.handle', $criteria->group, $whereParams);
			}
		}

		if ($criteria->username)
		{
			$whereConditions[] = DbHelper::parseParam('u.username', $criteria->username, $whereParams);
		}

		if ($criteria->firstName)
		{
			$whereConditions[] = DbHelper::parseParam('u.firstName', $criteria->firstName, $whereParams);
		}

		if ($criteria->lastName)
		{
			$whereConditions[] = DbHelper::parseParam('u.lastName', $criteria->lastName, $whereParams);
		}

		if ($criteria->email)
		{
			$whereConditions[] = DbHelper::parseParam('u.email', $criteria->email, $whereParams);
		}

		if ($criteria->admin)
		{
			$whereConditions[] = DbHelper::parseParam('u.admin', 1, $whereParams);
		}

		if ($criteria->status)
		{
			$whereConditions[] = DbHelper::parseParam('u.status', $criteria->status, $whereParams);
		}

		if ($criteria->lastLoginDate)
		{
			$whereConditions[] = DbHelper::parseParam('u.lastLoginDate', $criteria->lastLoginDate, $whereParams);
		}

		if ($whereConditions)
		{
			array_unshift($whereConditions, 'and');
			$query->where($whereConditions, $whereParams);
		}
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
		$hashedCode = craft()->security->hashString($unhashedCode);
		$userRecord->verificationCode = $hashedCode['hash'];
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
			$hashAndType = craft()->security->hashString($user->newPassword);

			$userRecord->password = $user->password = $hashAndType['hash'];
			$userRecord->encType = $user->encType = $hashAndType['encType'];
			$userRecord->status = $user->status = UserStatus::Active;
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
			if (!$user->id && craft()->systemSettings->getSetting('users', 'allowPublicRegistration', false))
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
