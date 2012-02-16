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
		$hashAndType = Blocks::app()->security->hashPassword($password);
		$user->password = $hashAndType['hash'];
		$user->enc_type = $hashAndType['encType'];
		$user->status = UserAccountStatus::PendingVerification;
		$user->password_reset_required = $passwordReset;

		$user->save();

		// refresh to get the user id
		$user->refresh();

		$authCode = new AuthCode();
		$authCode->user_id = $user->id;
		$date = new \DateTime();
		$authCode->date_issued = $date->getTimestamp();
		$dateInterval = new \DateInterval('PT'.ConfigHelper::getTimeInSeconds(Blocks::app()->config->getItem('authCodeExpiration')) .'S');
		$authCode->expiration_date = $date->add($dateInterval)->getTimestamp();
		$authCode->type = AuthorizationCodeType::Registration;
		$authCode->save();
		// refresh to get the db generated code.
		$authCode->refresh();

		return array('user' => $user, 'authCode' => $authCode);
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

	public function getTotalUsers()
	{
		return User::model()->count();
	}

	public function unlockUser(User $user)
	{
		$user->status = UserAccountStatus::Approved;
		$user->save();
		return $user;
	}

	public function getUserByAuthCode($authCode)
	{
		$authCode = AuthCode::model()->findByAttributes(array(
				'code' => $authCode,
		));

		return $authCode->user;
	}


}
