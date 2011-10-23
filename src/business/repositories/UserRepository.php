<?php

class UserRepository extends CApplicationComponent implements IUserRepository
{
	public function getAllUsers()
	{
		return Users::model()->findAll();
	}

	public function isUserNameInUse($userName)
	{
		if (StringHelper::IsNullOrEmpty($userName))
			throw new BlocksException('UserName is required.');

		$user = Users::model()->findByAttributes(array(
			'user_name' => $userName
		));

		return $user !== null;
	}

	public function isEmailInUse($email)
	{
		if (StringHelper::IsNullOrEmpty($email))
			throw new BlocksException('Email is required.');

		$user = Users::model()->findByAttributes(array(
			'user_name' => $email
		));

		return $user !== null;
	}

	public function registerUser($userName, $email, $firstName, $lastName, $password)
	{
		if (StringHelper::IsNullOrEmpty($userName) || StringHelper::IsNullOrEmpty($email) || StringHelper::IsNullOrEmpty($firstName) || StringHelper::IsNullOrEmpty($lastName) || StringHelper::IsNullOrEmpty($password))
			throw new BlocksException('UserName, email, first name, last name and password are required when registering a user.');

		if ($this->isUserNameInUse($userName))
			throw new BlocksException('UserName '.$userName.' is already in use.');

		if ($this->isEmailInUse($userName))
			throw new BlocksException('Email '.$email.' is already in use.');

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
