<?php
namespace Blocks;

/**
 * LoginModel class.
 * LoginModel is the data structure for keeping user login form data.
 * It is used by the 'login' action of 'sessionController'.
 */
class LoginModel extends BaseModel
{
	public function defineAttributes()
	{
		return array(
			'username'   => array(AttributeType::String, 'required' => true),
			'password'   => array(AttributeType::String, 'required' => true),
			'rememberMe' => AttributeType::Bool
		);
	}

	/**
	 * Stores the user identity.
	 *
	 * @access private
	 * @var UserIdentity
	 */
	private $_identity;

	/**
	 * Returns the user identity.
	 *
	 * @return UserIdentity
	 */
	public function getIdentity()
	{
		return $this->_identity;
	}

	/**
	 * Logs in the user using the given username and password in the model.
	 *
	 * @return boolean whether login is successful
	 */
	public function login()
	{
		if (!isset($this->_identity))
		{
			$this->_identity = new UserIdentity($this->username, $this->password);
			$this->_identity->authenticate();
		}

		if ($this->_identity->errorCode === UserIdentity::ERROR_NONE)
		{
			$timeOut = ConfigHelper::getTimeInSeconds(blx()->config->sessionTimeout);

			if ($this->rememberMe)
				$timeOut = ConfigHelper::getTimeInSeconds(blx()->config->rememberMeSessionTimeout);

			if (blx()->config->rememberUsernameEnabled === true)
			{
				$cookie = new \CHttpCookie('username', $this->username);
				$cookie->expire = DateTimeHelper::currentTime() + ConfigHelper::getTimeInSeconds(blx()->config->rememberUsernameTimeout);
				$cookie->httpOnly = true;
				blx()->request->cookies['username'] = $cookie;
			}

			return blx()->user->login($this->_identity, $timeOut);
		}
		else
			return false;
	}
}
