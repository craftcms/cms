<?php
namespace Blocks;

/**
 *
 */
class AccountsService extends \CApplicationComponent
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
		return User::model()->populateRecords($result);
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
			$whereConditions[] = DatabaseHelper::parseParam('id', $params['id'], $whereParams);

		if (!empty($params['username']))
			$whereConditions[] = DatabaseHelper::parseParam('username', $params['username'], $whereParams);

		if (!empty($params['first_name']))
			$whereConditions[] = DatabaseHelper::parseParam('first_name', $params['first_name'], $whereParams);

		if (!empty($params['last_name']))
			$whereConditions[] = DatabaseHelper::parseParam('last_name', $params['last_name'], $whereParams);

		if (!empty($params['email']))
			$whereConditions[] = DatabaseHelper::parseParam('email', $params['email'], $whereParams);

		if (!empty($params['admin']))
			$whereConditions[] = DatabaseHelper::parseParam('admin', 1, $whereParams);

		if (!empty($params['status']) && $params['status'] != '*')
			$whereConditions[] = DatabaseHelper::parseParam('status', $params['status'], $whereParams);

		if (!empty($params['last_login_date']))
			$whereConditions[] = DatabaseHelper::parseParam('last_login_date', $params['last_login_date'], $whereParams);

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
			'order' => 'date_created DESC'
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
		return User::model()->findById($id);
	}

	/**
	 * Gets a user by their username or email.
	 *
	 * @param string $usernameOrEmail
	 * @return User
	 */
	public function getUserByUsernameOrEmail($usernameOrEmail)
	{
		return User::model()->find(array(
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

		return User::model()->findByAttributes(array(
			'verification_code' => $code,
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
		return User::model()->exists(array(
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
		return User::model()->exists(array(
			'email=:email',
			array(':email' => $email),
		));
	}

	/**
	 * Generates a new verification code for a user.
	 *
	 * @param User $user
	 * @param bool $save
	 */
	public function generateVerificationCode(User $user, $save = true)
	{
		$verificationCode = StringHelper::UUID();
		$issuedDate = new DateTime();
		$duration = new \DateInterval('PT'.ConfigHelper::getTimeInSeconds(blx()->config->verificationCodeDuration) .'S');
		$expiryDate = $issuedDate->add($duration);

		$user->verification_code = $verificationCode;
		$user->verification_code_issued_date = $issuedDate->getTimestamp();
		$user->verification_code_expiry_date = $expiryDate->getTimestamp();

		if ($save)
			$user->save();
	}

	/**
	 * Activates a user, bypassing email verification.
	 *
	 * @param User $user
	 */
	public function activateUser(User $user)
	{
		$user->status = UserAccountStatus::Active;
		$user->verification_code = null;
		$user->verification_code_issued_date = null;
		$user->verification_code_expiry_date = null;
		$user->save();
	}

	/**
	 * Unlocks a user, bypassing the cooldown phase.
	 *
	 * @param User $user
	 */
	public function unlockUser(User $user)
	{
		$user->status = UserAccountStatus::Active;
		$user->failed_password_attempt_count = null;
		$user->failed_password_attempt_window_start = null;
		$user->cooldown_start = null;
		$user->save();
	}

	/**
	 * Suspends a user.
	 *
	 * @param User $user
	 */
	public function suspendUser(User $user)
	{
		$user->status = UserAccountStatus::Suspended;
		$user->save();
	}

	/**
	 * Unsuspends a user.
	 *
	 * @param User $user
	 */
	public function unsuspendUser(User $user)
	{
		$user->status = UserAccountStatus::Active;
		$user->save();
	}

	/**
	 * Changes a user's password.
	 *
	 * @param User $user
	 * @param string $newPassword
	 * @param bool $save
	 * @return bool
	 */
	public function changePassword(User $user, $newPassword, $save = true)
	{
		$hashAndType = blx()->security->hashPassword($newPassword);
		$user->password = $hashAndType['hash'];
		$user->enc_type = $hashAndType['encType'];
		$user->status = UserAccountStatus::Active;
		$user->last_password_change_date = DateTimeHelper::currentTime();
		$user->password_reset_required = false;

		if (!$save || $user->save())
			return true;
		else
			return false;
	}

	/**
	 * @param User $user
	 * @return bool
	 */
	public function forgotPassword(User $user)
	{
		$user = $this->generateVerificationCode($user);
		return blx()->email->sendEmailByKey($user, 'forgot_password');
	}

	/**
	 * Returns the remaining cooldown time for a user.
	 *
	 * @param User $user
	 * @return int
	 */
	public function getRemainingCooldownTime(User $user)
	{
		$cooldownEnd = $user->last_login_failed_date + ConfigHelper::getTimeInSeconds(blx()->config->failedPasswordCooldown);
		$cooldownRemaining = $cooldownEnd - DateTimeHelper::currentTime();

		if ($cooldownRemaining > 0)
			return $cooldownRemaining;

		return null;
	}

	/**
	 * Deletes a user.
	 *
	 * @param User $user
	 */
	public function deleteUser(User $user)
	{
		$user->archived_username = $user->username;
		$user->archived_email = $user->email;
		$user->username = '';
		$user->email = '';
		$user->status = UserAccountStatus::Archived;
		$user->save(false);
	}
}
