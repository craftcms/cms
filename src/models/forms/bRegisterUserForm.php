<?php

/**
 * bRegisterUserForm class.
 * bRegisterUserForm is the data structure for keeping data for registering a user.
 * It is used by the 'register' action of 'bUsersController'.
 */
class bRegisterUserForm extends CFormModel
{
	public $userName;
	public $email;
	public $firstName;
	public $lastName;
	public $sendRegistrationEmail;

	//public $password;
	//public $confirmPassword;

	/**
	 * Declares the validation rules.
	 * @return array of validation rules.
	 */
	public function rules()
	{
		return array(
			array('userName, email, firstName, lastName', 'required'),
			array('userName', 'length', 'min' => 3, 'max' => 250),
			array('userName', 'unique', 'className' => 'bUser', 'attributeName' => 'username'),
			array('email', 'unique', 'className' => 'bUser'),
			array('email', 'email'),
			array('email', 'length', 'min' => 5, 'max' => 250),
			array('firstName', 'length', 'min' => 1, 'max' => 100),
			array('lastName', 'length', 'min' => 1, 'max' => 100),
			//array('password', 'authenticate'),
			//array('password', 'compare', 'compareAttribute' => 'confirmPassword'),
		);
	}
}
