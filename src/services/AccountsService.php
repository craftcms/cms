<?php
namespace Blocks;

/**
 *
 */
class AccountsService extends \CApplicationComponent
{
	protected static $defaultParams = array(
		'offset' => 0,
		'limit' => 50,
		'status' => 'active',
	);

	/**
	 * Gets users.
	 *
	 * @param array $params
	 * @return array
	 */
	public function getUsers($params = array())
	{
		$params = array_merge(static::$defaultParams, $params);
		$query = blx()->db->createCommand()
			->from('users');

		// Where conditions

		$whereConditions = array('and');
		$whereParams = array();

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

		if (!empty($params['status']))
			$whereConditions[] = DatabaseHelper::parseParam('status', $params['status'], $whereParams);

		if (!empty($params['last_login_date']))
			$whereConditions[] = DatabaseHelper::parseParam('last_login_date', $params['last_login_date'], $whereParams);

		$query->where($whereConditions, $whereParams);

		// Order

		if (!empty($params['order']))
			$query->order($params['order']);

		// Offset and Limit

		if (!empty($params['offset']))
			$query->offset($params['offset']);

		if (!empty($params['limit']))
			$query->limit($params['limit']);

		// Find the users

		$result = $query->queryAll();
		$users = User::model()->populateRecords($result);
		return $users;
	}

	/**
	 * Gets admins.
	 *
	 * @param array $params
	 * @return array
	 */
	public function getAdmins($params = array())
	{
		return $this->getUsers(array_merge($params, array(
			'admin' => true
		)));
	}

	/**
	 * Returns the 50 most recent users
	 * @return mixed
	 */
	public function getRecentUsers()
	{
		return User::model()->recentlyCreated()->findAll();
	}

	/**
	 * @return string
	 */
	public function getChangePasswordUrl()
	{
		return 'account/password';
	}

	/**
	 * @return string
	 */
	public function getVerifyAccountUrl()
	{
		return 'verify';
	}

	/**
	 * @param $userId
	 * @return mixed
	 */
	public function getUserById($userId)
	{
		$user = User::model()->findById($userId);
		return $user;
	}

	/**
	 * Returns a user by its username or email
	 * @param string $usernameOrEmail
	 * @return User
	 */
	public function getUserByUsernameOrEmail($usernameOrEmail)
	{
		$user = User::model()->find(array(
			'condition' => 'username=:usernameOrEmail OR email=:usernameOrEmail',
			'params' => array(':usernameOrEmail' => $usernameOrEmail),
		));

		return $user;
	}

	/**
	 * Gets a user by a verification code
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
	 * Returns the User model of the currently logged in user and null if is user is not logged in.
	 * @return User The model of the logged in user.
	 */
	public function getCurrentUser()
	{
		return $this->getUserById(isset(blx()->user) ? blx()->user->getId() : null);
	}

	/**
	 * @param $userName
	 * @return mixed
	 */
	public function isUserNameInUse($userName)
	{
		$exists = User::model()->exists(array(
			'username=:userName',
			array(':userName' => $userName),
		));

		return $exists;
	}

	/**
	 * @param $email
	 * @return mixed
	 */
	public function isEmailInUse($email)
	{
		$exists = User::model()->exists(array(
			'email=:userName',
			array(':email' => $email),
		));

		return $exists;
	}

	/**
	 * Generates a new verification code for a user.
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
	 * @param $userId
	 * @return array
	 */
	public function getGroupsByUserId($userId)
	{
		$groups = blx()->db->createCommand()
			->select('g.*')
			->from('groups g')
			->join('usergroups ug', 'g.id = ug.group_id')
			->join('users u', 'ug.user_id = u.id')
			->where('u.id=:userId', array(':userId' => $userId))
			->queryAll();

		return $groups;
	}

	/**
	 * @param $groupId
	 * @return array
	 */
	public function getUsersByGroupId($groupId)
	{
		$groups = blx()->db->createCommand()
			->select('u.*')
			->from('groups g')
			->join('usergroups ug', 'g.id = ug.group_id')
			->join('users u', 'ug.user_id = u.id')
			->where('g.id=:groupId', array(':groupId' => $groupId))
			->queryAll();

		return $groups;
	}

	/**
	 * @return UserGroup
	 */
	public function getAllGroups()
	{
		return UserGroup::model()->findAll();
	}

	/**
	 * @return mixed
	 */
	public function getTotalUsers()
	{
		return User::model()->count();
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
	 * @param User $user
	 * @param      $newPassword
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
	 * @param User $user
	 * @return null
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
