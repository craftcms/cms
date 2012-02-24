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
			array('password', 'authenticate'),
		);
	}

	/**
	 * Authenticates the password.
	 * This is the 'authenticate' validator as declared in rules().
	 */
	public function authenticate()
	{
		if (!$this->hasErrors())
		{
			$this->_identity = new UserIdentity($this->loginName, $this->password);
			if (!$this->_identity->authenticate())
			{
				if ($this->_identity->errorCode == UserIdentity::ERROR_ACCOUNT_LOCKED)
					$this->addError('loginName', 'This account has been locked.');
				elseif ($this->_identity->errorCode == UserIdentity::ERROR_ACCOUNT_COOLDOWN)
					$this->addError('loginName', 'Cooldown man.  '.$this->_identity->cooldownTimeRemaining.' seconds remaining.');
				else
					$this->addError('password', 'Incorrect login name or password.');
			}
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
			$timeOut = ConfigHelper::getTimeInSeconds(Blocks::app()->config->getItem('sessionTimeout'));

			if ($this->rememberMe)
				$timeOut = ConfigHelper::getTimeInSeconds(Blocks::app()->config->getItem('rememberMeSessionTimeout'));

			if (Blocks::app()->config->getItem('rememberUsernameEnabled') == true)
			{
				$cookie = new \CHttpCookie('loginName', $this->loginName);
				$cookie->expire = DateTimeHelper::currentTime() + ConfigHelper::getTimeInSeconds(Blocks::app()->config->getItem('rememberUsernameTimeout'));
				$cookie->httpOnly = true;
				Blocks::app()->request->cookies['loginName'] = $cookie;
			}

			return Blocks::app()->user->login($this->_identity, $timeOut);
		}

		return false;
	}
}
