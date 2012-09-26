<?php
namespace Blocks;

/**
 *
 */
class AccountsService extends BaseApplicationComponent
{
	private $_currentUser;

	/**
	 * Gets users.
	 *
	 * @param UserParams|null $params
	 * @return array
	 */
	public function getUsers(UserParams $params = null)
	{
		if (!$params)
		{
			$params = new UserParams();
		}

		$query = blx()->db->createCommand()
			->select('u.*')
			->from('users u');

		$this->_applyUserConditions($query, $params);

		if ($params->order)
		{
			$query->order($params->order);
		}

		if ($params->offset)
		{
			$query->offset($params->offset);
		}

		if ($params->limit)
		{
			$query->limit($params->limit);
		}

		$result = $query->queryAll();
		return UserRecord::model()->populateRecords($result);
	}

	/**
	 * Gets the total number of users.
	 *
	 * @param array $params
	 * @return int
	 * @return int
	 */
	public function getTotalUsers($params = array())
	{
		if (!$params)
		{
			$params = new UserParams();
		}

		$query = blx()->db->createCommand()
			->select('count(u.id)')
			->from('users u');

		$this->_applyUserConditions($query, $params);

		return (int) $query->queryScalar();
	}

	/**
	 * Applies WHERE conditions to a DbCommand query for users.
	 *
	 * @access private
	 * @param DbCommand $query
	 * @param           $params
	 * @param array     $params
	 */
	private function _applyUserConditions($query, $params)
	{
		$whereConditions = array();
		$whereParams = array();

		if ($params->id)
		{
			$whereConditions[] = DbHelper::parseParam('u.id', $params->id, $whereParams);
		}
		/* BLOCKSPRO ONLY */

		if ($params->groupId || $params->group)
		{
			$query->join('usergroups_users gu', 'gu.userId = u.id');

			if ($params->groupId)
			{
				$whereConditions[] = DbHelper::parseParam('gu.groupId', $params->groupId, $whereParams);
			}

			if ($params->group)
			{
				$query->join('usergroups g', 'g.id = gu.groupId');
				$whereConditions[] = DbHelper::parseParam('g.handle', $params->group, $whereParams);
			}
		}
		/* end BLOCKSPRO ONLY */

		if ($params->username)
		{
			$whereConditions[] = DbHelper::parseParam('u.username', $params->username, $whereParams);
		}

		if ($params->firstName)
		{
			$whereConditions[] = DbHelper::parseParam('u.firstName', $params->firstName, $whereParams);
		}

		if ($params->lastName)
		{
			$whereConditions[] = DbHelper::parseParam('u.lastName', $params->lastName, $whereParams);
		}

		if ($params->email)
		{
			$whereConditions[] = DbHelper::parseParam('u.email', $params->email, $whereParams);
		}

		if ($params->admin)
		{
			$whereConditions[] = DbHelper::parseParam('u.admin', 1, $whereParams);
		}

		if ($params->status && $params->status != '*')
		{
			$whereConditions[] = DbHelper::parseParam('u.status', $params->status, $whereParams);
		}

		if ($params->lastLoginDate)
		{
			$whereConditions[] = DbHelper::parseParam('u.lastLoginDate', $params->lastLoginDate, $whereParams);
		}

		if ($whereConditions)
		{
			array_unshift($whereConditions, 'and');
			$query->where($whereConditions, $whereParams);
		}
	}

	/**
	 * Gets a user by their ID.
	 *
	 * @param $id
	 * @return User
	 */
	public function getUserById($id)
	{
		return UserRecord::model()->findById($id);
	}

	/**
	 * Gets a user by their username or email.
	 *
	 * @param string $usernameOrEmail
	 * @return User
	 */
	public function getUserByUsernameOrEmail($usernameOrEmail)
	{
		return UserRecord::model()->find(array(
			'condition' => 'username=:usernameOrEmail OR email=:usernameOrEmail',
			'params' => array(':usernameOrEmail' => $usernameOrEmail),
		));
	}

	/**
	 * Gets a user by a verification code.
	 *
	 * @param string $code
	 * @return User
	 */
	public function getUserByVerificationCode($code)
	{
		if ($code)
		{
			return UserRecord::model()->findByAttributes(array(
				'verificationCode' => $code,
			));
		}
	}

	/**
	 * Gets the currently logged-in user.
	 *
	 * @return User
	 */
	public function getCurrentUser()
	{
		if (!empty(blx()->user))
		{
			if (!isset($this->_currentUser))
			{
				$this->_currentUser = $this->getUserById(blx()->user->getId());
			}

			return $this->_currentUser;
		}
	}

	/**
	 * @return string
	 */
	public function getVerifyAccountUrl()
	{
		return 'verify';
	}

	/**
	 * Returns whether a username is already in use.
	 *
	 * @param string $username
	 * @return bool
	 */
	public function isUserNameInUse($username)
	{
		return UserRecord::model()->exists(array(
			'username=:username',
			array(':username' => $username),
		));
	}

	/**
	 * Returns whether an email is already in use.
	 *
	 * @param string $email
	 * @return bool
	 */
	public function isEmailInUse($email)
	{
		return UserRecord::model()->exists(array(
			'email=:email',
			array(':email' => $email),
		));
	}

	/**
	 * Generates a new verification code for a user.
	 *
	 * @param UserRecord $user
	 * @param bool $save
	 */
	public function generateVerificationCode(UserRecord $user, $save = true)
	{
		$verificationCode = StringHelper::UUID();
		$issuedDate = new DateTime();
		$duration = new \DateInterval('PT'.ConfigHelper::getTimeInSeconds(blx()->config->verificationCodeDuration) .'S');
		$expiryDate = $issuedDate->add($duration);

		$user->verificationCode = $verificationCode;
		$user->verificationCodeIssuedDate = $issuedDate->getTimestamp();
		$user->verificationCodeExpiryDate = $expiryDate->getTimestamp();

		if ($save)
		{
			$user->save();
		}
	}

	/**
	 * Activates a user, bypassing email verification.
	 *
	 * @param UserRecord $user
	 */
	public function activateUser(UserRecord $user)
	{
		$user->status = UserAccountStatus::Active;
		$user->verificationCode = null;
		$user->verificationCodeIssuedDate = null;
		$user->verificationCodeExpiryDate = null;
		$user->save();
	}

	/**
	 * Unlocks a user, bypassing the cooldown phase.
	 *
	 * @param UserRecord $user
	 */
	public function unlockUser(UserRecord $user)
	{
		$user->status = UserAccountStatus::Active;
		$user->failedPasswordAttemptCount = null;
		$user->failedPasswordAttemptWindowStart = null;
		$user->cooldownStart = null;
		$user->save();
	}

	/**
	 * Suspends a user.
	 *
	 * @param UserRecord $user
	 */
	public function suspendUser(UserRecord $user)
	{
		$user->status = UserAccountStatus::Suspended;
		$user->save();
	}

	/**
	 * Unsuspends a user.
	 *
	 * @param UserRecord $user
	 */
	public function unsuspendUser(UserRecord $user)
	{
		$user->status = UserAccountStatus::Active;
		$user->save();
	}

	/**
	 * Changes a user's password.
	 *
	 * @param UserRecord $user
	 * @param string $newPassword
	 * @param bool $save
	 * @return bool
	 */
	public function changePassword(UserRecord $user, $newPassword, $save = true)
	{
		$hashAndType = blx()->security->hashPassword($newPassword);
		$user->password = $hashAndType['hash'];
		$user->encType = $hashAndType['encType'];
		$user->status = UserAccountStatus::Active;
		$user->lastPasswordChangeDate = DateTimeHelper::currentTime();
		$user->passwordResetRequired = false;

		if (!$save || $user->save())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param UserRecord $user
	 * @return bool
	 */
	public function forgotPassword(UserRecord $user)
	{
		$user = $this->generateVerificationCode($user);
		return blx()->email->sendEmailByKey($user, 'forgot_password');
	}

	/**
	 * Returns the remaining cooldown time for a user.
	 *
	 * @param UserRecord $user
	 * @return int
	 */
	public function getRemainingCooldownTime(UserRecord $user)
	{
		$cooldownEnd = $user->lastLoginFailedDate + ConfigHelper::getTimeInSeconds(blx()->config->failedPasswordCooldown);
		$cooldownRemaining = $cooldownEnd - DateTimeHelper::currentTime();

		if ($cooldownRemaining > 0)
		{
			return $cooldownRemaining;
		}
	}

	/**
	 * Deletes a user.
	 *
	 * @param UserRecord $user
	 */
	public function deleteUser(UserRecord $user)
	{
		$user->archivedUsername = $user->username;
		$user->archivedEmail = $user->email;
		$user->username = '';
		$user->email = '';
		$user->status = UserAccountStatus::Archived;
		$user->save(false);
	}
}
