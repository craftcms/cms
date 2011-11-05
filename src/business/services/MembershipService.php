<?php

class MembershipService extends CApplicationComponent implements IMembershipService
{
	public function getAllUsers()
	{
		return Users::model()->findAll();
	}

	public function isUserNameInUse($userName)
	{
		$exists = Users::model()->exists(array(
			'user_name=:userName',
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
		$user->user_name = $userName;
		$user->email = $email;
		$user->first_name = $firstName;
		$user->last_name = $lastName;
		$user->password = $password;
		$user->salt = $password;
		$user->save();

		return $user;
	}
}
