<?php
namespace Blocks;

/**
 *
 */
class UsersService extends BaseApplicationComponent
{
	/**
	 * Gets a user by their ID.
	 *
	 * @param $id
	 * @return UserModel
	 */
	public function getUserById($id)
	{
		$userRecord = UserRecord::model()->findById($id);

		if ($userRecord)
		{
			return UserModel::populateModel($userRecord);
		}
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
	 * Gets a user by a verification code.
	 *
	 * @param string $code
	 * @return UserModel
	 */
	public function getUserByVerificationCode($code)
	{
		if ($code)
		{
			$date = DateTimeHelper::currentUTCDateTime();
			$duration = new DateInterval(blx()->config->get('verificationCodeDuration'));
			$date->sub($duration);

			$userRecord = UserRecord::model()->find(
				'verificationCode = :code and verificationCodeIssuedDate > :date',
				array(':code' => $code, ':date' => DateTimeHelper::formatTimeForDb($date->getTimestamp()))
			);

			if ($userRecord)
			{
				return UserModel::populateModel($userRecord);
			}
		}
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

		$query = blx()->db->createCommand()
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

		$query = blx()->db->createCommand()
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

		$query = blx()->db->createCommand()
			->select('count(u.id)')
			->from('users u');

		$this->_applyUserConditions($query, $criteria);

		return (int) $query->queryScalar();
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
				throw new Exception(Blocks::t('No user exists with the ID “{id}”', array('id' => $user->id)));
			}

			$oldUsername = $userRecord->username;
		}
		else
		{
			$userRecord = new UserRecord();
		}

		if (!$user->emailFormat)
		{
			$user->emailFormat = 'text';
		}

		if (!$user->language)
		{
			$user->language = blx()->language;
		}

		$userRecord->username = $user->username;
		$userRecord->firstName = $user->firstName;
		$userRecord->lastName = $user->lastName;
		$userRecord->email = $user->email;
		$userRecord->emailFormat = $user->emailFormat;
		$userRecord->admin = $user->admin;
		$userRecord->passwordResetRequired = $user->passwordResetRequired;
		$userRecord->language = $user->language;

		if ($user->newPassword)
		{
			$this->_setPasswordOnUserRecord($user, $userRecord);
		}

		if ($userRecord->validate() && !$user->hasErrors())
		{
			if ($user->verificationRequired)
			{
				$userRecord->status = $user->status = UserStatus::Pending;
				$this->_setVerificationCodeOnUserRecord($userRecord);
			}

			$userRecord->save();

			$user->id = $userRecord->id;

			if ($user->verificationRequired)
			{
				blx()->email->sendEmailByKey($user, 'verify_email', array(
					'link' => $this->_getVerifyAccountUrl($userRecord)
				));
			}

			if (!$isNewUser)
			{
				// Has the username changed?
				if ($user->username != $oldUsername)
				{
					// Rename the user's photo directory
					$oldFolder = blx()->path->getUserPhotosPath().$oldUsername;
					$newFolder = blx()->path->getUserPhotosPath().$user->username;

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

			return true;
		}
		else
		{
			$user->addErrors($userRecord->getErrors());
			return false;
		}
	}

	/**
	 * Sends a verification email
	 */
	public function sendVerificationEmail(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);
		$this->_setVerificationCodeOnUserRecord($userRecord);
		$userRecord->save();

		return blx()->email->sendEmailByKey($user, 'verify_email', array(
			'link' => $this->_getVerifyAccountUrl($userRecord)
		));
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
		$this->_setVerificationCodeOnUserRecord($userRecord);
		$userRecord->save();

		return blx()->email->sendEmailByKey($user, 'forgot_password', array(
			'link' => $this->_getVerifyAccountUrl($userRecord)
		));
	}

	/**
	 * Sets a user record up for a new verification code without saving it.
	 *
	 * @access private
	 * @param UserRecord $userRecord
	 */
	private function _setVerificationCodeOnUserRecord(UserRecord $userRecord)
	{
		$userRecord->verificationCode = StringHelper::UUID();
		$userRecord->verificationCodeIssuedDate = DateTimeHelper::currentUTCDateTime();
	}

	/**
	 * Gets the account verification URL for a user record.
	 *
	 * @access private
	 * @param UserRecord $userRecord
	 * @return string
	 * @throws Exception
	 */
	private function _getVerifyAccountUrl(UserRecord $userRecord)
	{
		if ($userRecord->verificationCode)
		{
			return UrlHelper::getUrl(blx()->config->get('resetPasswordPath'), array(
				'code' => $userRecord->verificationCode
			));
		}
		else
		{
			throw new Exception(Blocks::t('This user doesn’t have a verification code set.'));
		}
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
			$hashAndType = blx()->security->hashPassword($user->newPassword);

			$userRecord->password = $user->password = $hashAndType['hash'];
			$userRecord->encType = $user->encType = $hashAndType['encType'];
			$userRecord->status = $user->status = UserStatus::Active;
			$userRecord->invalidLoginWindowStart = null;
			$userRecord->invalidLoginCount = $user->invalidLoginCount = null;
			$userRecord->verificationCode = null;
			$userRecord->verificationCodeIssuedDate = null;
			$userRecord->passwordResetRequired = $user->passwordResetRequired = false;
			$userRecord->lastPasswordChangeDate = $user->lastPasswordChangeDate = DateTimeHelper::currentUTCDateTime();

			$user->newPassword = null;

			return true;
		}
		else
		{
			$user->addErrors(array(
				'newPassword' => $passwordModel->getErrors('password')
			));

			return false;
		}
	}

	/**
	 * Handles a successful login for a user.
	 *
	 * @param UserModel $user
	 * @param string $authSessionToken
	 * @return bool
	 */
	public function handleSuccessfulLogin(UserModel $user, $authSessionToken)
	{
		$userRecord = $this->_getUserRecordById($user->id);

		$userRecord->authSessionToken = $authSessionToken;
		$userRecord->lastLoginDate = $user->lastLoginDate = DateTimeHelper::currentUTCDateTime();
		$userRecord->lastLoginAttemptIPAddress = blx()->request->getUserHostAddress();
		$userRecord->invalidLoginWindowStart = null;
		$userRecord->invalidLoginCount = $user->invalidLoginCount = null;
		$userRecord->verificationCode = null;
		$userRecord->verificationCodeIssuedDate = null;

		return $userRecord->save();
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
		$userRecord->lastLoginAttemptIPAddress = blx()->request->getUserHostAddress();

		if ($this->_isUserInsideInvalidLoginWindow($userRecord))
		{
			$userRecord->invalidLoginCount++;

			// Was that one bad password too many?
			if ($userRecord->invalidLoginCount >= blx()->config->get('maxInvalidLogins'))
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
	 * Determines if a user is within their invalid login window.
	 *
	 * @param UserRecord $userRecord
	 * @return bool
	 */
	private function _isUserInsideInvalidLoginWindow(UserRecord $userRecord)
	{
		if ($userRecord->invalidLoginWindowStart)
		{
			$duration = new DateInterval(blx()->config->get('invalidLoginWindowDuration'));
			$end = $userRecord->invalidLoginWindowStart->add($duration);
			return ($end >= DateTimeHelper::currentUTCDateTime());
		}
		else
		{
			return false;
		}
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
	 * Archives a user.
	 *
	 * @param UserModel $user
	 * @return bool
	 */
	public function archiveUser(UserModel $user)
	{
		$userRecord = $this->_getUserRecordById($user->id);

		$userRecord->status = $user->status = UserStatus::Archived;
		$userRecord->archivedUsername = $user->username;
		$userRecord->archivedEmail = $user->email;
		$userRecord->username = '';
		$userRecord->email = '';

		// Delete their photo folder
		$photoFolder = blx()->path->getUserPhotosPath().$userRecord->archivedUsername;
		if (IOHelper::folderExists($photoFolder))
		{
			IOHelper::deleteFolder($photoFolder);
		}

		return $userRecord->save(false);
	}

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
			throw new Exception(Blocks::t('No user exists with the ID “{id}”', array('id' => $userId)));
		}

		return $userRecord;
	}

	/**
	 * Applies WHERE conditions to a DbCommand query for users.
	 *
	 * @access private
	 * @param DbCommand $query
	 * @param           $criteria
	 * @param array     $criteria
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

		if ($criteria->status && $criteria->status != '*')
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
}
