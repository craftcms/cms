<?php

class MembershipService extends CApplicationComponent implements IMembershipService
{
	public function getAllUsers()
	{
		return Users::model()->findAll();
	}

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

	public function isUserNameInUse($userName)
	{
		$exists = Users::model()->exists(array(
			'username=:userName',
			array(':userName' => $userName),
		));

		return $exists;
	}

	public function isEmailInUse($email)
	{
		$exists = Users::model()->exists(array(
			'email=:userName',
			array(':email' => $email),
		));

		return $exists;
	}

	public function registerUser($userName, $email, $firstName, $lastName, $password)
	{
		$user = new Users();
		$user->username = $userName;
		$user->email = $email;
		$user->first_name = $firstName;
		$user->last_name = $lastName;
		$user->password = $password;
		$user->salt = $password;
		$user->save();

		return $user;
	}

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

	public function getAllGroups()
	{
		return UserGroups::model()->findAll();
	}
}
