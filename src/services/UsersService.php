<?php
namespace Blocks;

/**
 *
 */
class UsersService extends \CApplicationComponent
{
	/**
	 * @return User
	 */
	public function getAllUsers()
	{
		return User::model()->findAll();
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
	 * @param User                             $user
	 * @param                                  $password
	 * @param bool                             $passwordReset
	 *
	 * @return User
	 */
	public function registerUser(User $user, $password, $passwordReset = true)
	{
		// if the password is null, we know someone on the back-end wants to create the account.
		if ($password !== null)
		{
			$hashAndType = blx()->security->hashPassword($password);
			$user->password = $hashAndType['hash'];
			$user->enc_type = $hashAndType['encType'];
		}

		$user->password_reset_required = $passwordReset;

		$user->status = UserAccountStatus::Pending;
		$user = $this->generateActivationCodeForUser($user);

		return $user;
	}

	/**
	 * @param User $user
	 * @return User
	 */
	public function generateActivationCodeForUser(User $user)
	{
		$activationCode = StringHelper::UUID();
		$user->activationcode = $activationCode;
		$date = new DateTime;
		$user->activationcode_issued_date = $date->getTimestamp();
		$dateInterval = new \DateInterval('PT'.ConfigHelper::getTimeInSeconds(blx()->config->activationCodeExpiration) .'S');
		$user->activationcode_expire_date = $date->add($dateInterval)->getTimestamp();
		$user->save();

		return $user;
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
	 * @param User $user
	 * @return User
	 */
	public function unlockUser(User $user)
	{
		$user->status = UserAccountStatus::Active;
		$user->save();
		return $user;
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
			$user = $this->generateActivationCodeForUser($user);

			$site = blx()->sites->getCurrentSite();
			if (($emailStatus = blx()->email->sendForgotPasswordEmail($user, $site)) == true)
				return true;

		return false;
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
