<?php
namespace Blocks;

/**
 *
 */
class UsersService extends BaseService
{
	/**
	 * @return User
	 */
	public function getAll()
	{
		return User::model()->findAll();
	}

	/**
	 * Returns the 50 most recent users
	 * @return mixed
	 */
	public function getRecent()
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
	 * @return string
	 */
	public function getForgotPasswordUrl()
	{
		return 'forgot';
	}

	/**
	 * @param $siteId
	 * @return array
	 */
	public function getAllUsersBySiteId($siteId)
	{
		$users = Blocks::app()->db->createCommand()
			->select('u.*')
			->from('{{users}} g')
			->join('{{usergroups}} ug', 'u.id = ug.users_id')
			->join('{{sites}} s', 'ug.site_id = s.id')
			->where('s.id=:siteId', array(':siteId' => $siteId))
			->queryAll();

		return $users;
	}

	/**
	 * @param $userId
	 * @return mixed
	 */
	public function getById($userId)
	{
		$user = User::model()->findById($userId);
		return $user;
	}

	/**
	 * @param $loginName
	 * @return mixed
	 */
	public function getByLoginName($loginName)
	{
		$user = User::model()->find(array(
			'condition' => 'username=:userName OR email=:email',
			'params' => array(':userName' => $loginName, ':email' => $loginName),
		));

		return $user;
	}

	/**
	 * Returns the User model of the currently logged in user and null if is user is not logged in.
	 * @return User The model of the logged in user.
	 */
	public function getCurrent()
	{
		$user = $this->getById(Blocks::app()->user->id);
		return $user;
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
	 * @param \Blocks\User                     $user
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
			$hashAndType = Blocks::app()->security->hashPassword($password);
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
		$activationCode = Blocks::app()->db->createCommand()->getUUID();
		$user->activationcode = $activationCode;
		$date = new \DateTime();
		$user->activationcode_issued_date = $date->getTimestamp();
		$dateInterval = new \DateInterval('PT'.ConfigHelper::getTimeInSeconds(Blocks::app()->config->getItem('activationCodeExpiration')) .'S');
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
		$groups = Blocks::app()->db->createCommand()
			->select('g.*')
			->from('{{groups}} g')
			->join('{{usergroups}} ug', 'g.id = ug.group_id')
			->join('{{users}} u', 'ug.user_id = u.id')
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
		$groups = Blocks::app()->db->createCommand()
			->select('u.*')
			->from('{{groups}} g')
			->join('{{usergroups}} ug', 'g.id = ug.group_id')
			->join('{{users}} u', 'ug.user_id = u.id')
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
	 * @param $newPassword
	 * @return \Blocks\User|bool
	 */
	public function changePassword(User $user, $newPassword)
	{
		$hashAndType = Blocks::app()->security->hashPassword($newPassword);
		$user->password = $hashAndType['hash'];
		$user->enc_type = $hashAndType['encType'];
		$user->status = UserAccountStatus::Active;

		if ($user->save())
			return $user;

		return false;
	}

	/**
	 * @param \Blocks\User $user
	 * @return bool
	 */
	public function forgotPassword(User $user)
	{
			$user = $this->generateActivationCodeForUser($user);

			$site = Blocks::app()->sites->currentSite;
			if (($emailStatus = Blocks::app()->email->sendForgotPasswordEmail($user, $site)) == true)
				return true;

		return false;
	}
}
