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
	public $rememberMe;

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
		);
	}

	public function getIdentity()
	{
		return $this->_identity;
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
			$timeOut = ConfigHelper::getTimeInSeconds(b()->config->getItem('sessionTimeout'));

			if ($this->rememberMe)
				$timeOut = ConfigHelper::getTimeInSeconds(b()->config->getItem('rememberMeSessionTimeout'));

			if (b()->config->getItem('rememberUsernameEnabled') == true)
			{
				$cookie = new \CHttpCookie('loginName', $this->loginName);
				$cookie->expire = DateTimeHelper::currentTime() + ConfigHelper::getTimeInSeconds(b()->config->getItem('rememberUsernameTimeout'));
				$cookie->httpOnly = true;
				b()->request->cookies['loginName'] = $cookie;
			}

			return b()->user->login($this->_identity, $timeOut);
		}

		return false;
	}
}
