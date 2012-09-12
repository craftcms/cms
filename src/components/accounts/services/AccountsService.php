<?php
namespace Blocks;

/**
 *
 */
class AccountsService extends BaseApplicationComponent
{
	/**
	 * The default parameters for getUsers() and getTotalUsers().
	 *
	 * @access private
	 * @static
	 */
	private static $_defaultUserParams = array(
		'status' => 'active',
		'offset' => 0,
		'limit' => 50,
	);

	private $_currentUser;

	/**
	 * Gets users.
	 *
	 * @param array $params
	 * @return array
	 */
	public function getUsers($params = array())
	{
		$params = array_merge(static::$_defaultUserParams, $params);
		$query = blx()->db->createCommand()
			->from('users');

		$this->_applyUserConditions($query, $params);

		if (!empty($params['order']))
			$query->order($params['order']);

		if (!empty($params['offset']))
			$query->offset($params['offset']);

		if (!empty($params['limit']))
			$query->limit($params['limit']);

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
		$params = array_merge(static::$_defaultUserParams, $params);
		$query = blx()->db->createCommand()
			->select('count(id)')
			->from('users');

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

		if (!empty($params['id']))
			$whereConditions[] = DbHelper::parseParam('id', $params['id'], $whereParams);

		if (!empty($params['username']))
			$whereConditions[] = DbHelper::parseParam('username', $params['username'], $whereParams);

		if (!empty($params['firstName']))
			$whereConditions[] = DbHelper::parseParam('firstName', $params['firstName'], $whereParams);

		if (!empty($params['lastName']))
			$whereConditions[] = DbHelper::parseParam('lastName', $params['lastName'], $whereParams);

		if (!empty($params['email']))
			$whereConditions[] = DbHelper::parseParam('email', $params['email'], $whereParams);

		if (!empty($params['admin']))
			$whereConditions[] = DbHelper::parseParam('admin', 1, $whereParams);

		if (!empty($params['status']) && $params['status'] != '*')
			$whereConditions[] = DbHelper::parseParam('status', $params['status'], $whereParams);

		if (!empty($params['lastLoginDate']))
			$whereConditions[] = DbHelper::parseParam('lastLoginDate', $params['lastLoginDate'], $whereParams);

		if ($whereConditions)
		{
			array_unshift($whereConditions, 'and');
			$query->where($whereConditions, $whereParams);
		}
	}

	/**
	 * Gets the most recent users.
	 *
	 * @param array $params
	 * @return array
	 */
	public function getRecentUsers($params = array())
	{
		return $this->getUsers(array_merge($params, array(
			'order' => 'dateCreated DESC'
		)));
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
		if (!$code)
			return null;

		return UserRecord::model()->findByAttributes(array(
			'verificationCode' => $code,
		));
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
				$this->_currentUser = $this->getUserById(blx()->user->getId());

			return $this->_currentUser;
		}
		else
			return null;
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
			$user->save();
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
			return true;
		else
			return false;
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
			return $cooldownRemaining;

		return null;
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
