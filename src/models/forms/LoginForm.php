<?php
namespace Blocks;

/**
 * LoginForm class.
 * LoginForm is the data structure for keeping user login form data.
 * It is used by the 'login' action of 'sessionController'.
 */
class LoginForm extends \CFormModel
{
	public $loginName;
	public $password;

	private $_identity;

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required and the password needs to be authenticated.
	 * @return array of validation rules.
	 */
	public function rules()
	{
		return array(
			array('loginName, password', 'required'),
			array('password', 'authenticate'),
		);
	}

	/**
	 * Authenticates the password.
	 * This is the 'authenticate' validator as declared in rules().
	 *
	 * @param $attribute
	 * @param $params
	 */
	public function authenticate($attribute, $params)
	{
		if (!$this->hasErrors())
		{
			$this->_identity = new UserIdentity($this->loginName, $this->password);
			if (!$this->_identity->authenticate())
				$this->addError('password', 'Incorrect login name or password.');
		}
	}

	/**
	 * Logs in the user using the given username and password in the model.
	 * @return boolean whether login is successful
	 */
	public function login()
	{
		if ($this->_identity === null)
		{
			$this->_identity = new UserIdentity($this->loginName, $this->password);
			$this->_identity->authenticate();
		}
		if($this->_identity->errorCode === UserIdentity::ERROR_NONE)
		{
			Blocks::app()->user->login($this->_identity, ConfigHelper::getTimeInSeconds('sessionTimeout'));
			return true;
		}
		else
			return false;
	}
}
