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
	public function getUserById($userId)
	{
		$user = User::model()->findByPk($userId);
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
	 * @param      $userName
	 * @param      $email
	 * @param      $firstName
	 * @param      $lastName
	 * @param      $password
	 * @param bool $passwordReset
	 *
	 * @return User
	 */
	public function registerUser($userName, $email, $firstName, $lastName, $password, $passwordReset = false)
	{
		$hashAndType = Blocks::app()->security->hashPassword($password);

		$user = new User();
		$user->username = $userName;
		$user->email = $email;
		$user->first_name = $firstName;
		$user->last_name = $lastName;
		$user->password = $hashAndType['hash'];
		$user->enc_type = $hashAndType['encType'];
		$user->password_reset_required = $passwordReset;
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
}
