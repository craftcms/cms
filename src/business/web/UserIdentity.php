<?php
namespace Blocks;

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends \CUserIdentity
{
	private $_id;
	private $_authToken;

	public $loginName;
	public $password;

	const ERROR_ACCOUNT_LOCKED = 50;

	/**
	 * Constructor.
	 * @param string $loginName
	 * @param string $password
	 */
	public function __construct($loginName, $password)
	{
		$this->loginName = $loginName;
		$this->password = $password;
	}

	/**
	 * Returns the display name for the identity.
	 * The default implementation simply returns {@link loginName}.
	 * This method is required by {@link IUserIdentity}.
	 * @return string the display name for the identity.
	 */
	public function getName()
	{
		return $this->loginName;
	}

	/**
	 * Authenticates a user against the database.
	 *
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{
		$user = User::model()->find(array(
			'condition' => 'username=:userName OR email=:email',
			'params' => array(':userName' => $this->loginName, ':email' => $this->loginName),
		));

		if ($user === null)
			$this->errorCode = self::ERROR_USERNAME_INVALID;
		else
		{
			// if the account is locked, don't even attempt to log in.
			if ($user->status == UserAccountStatus::Locked)
			{
				$this->errorCode = self::ERROR_ACCOUNT_LOCKED;
			}
			else
			{
				$checkPassword = Blocks::app()->security->checkPassword($this->password, $user->password, $user->enc_type);

				if (!$checkPassword)
				{
					$this->errorCode = self::ERROR_PASSWORD_INVALID;

					// get the current failed password attempt count.
					$currentFailedCount = $user->failed_password_attempt_count;

					// if it's empty, this is the first failed attempt we have for the current window.
					if (StringHelper::isNullOrEmpty($currentFailedCount))
					{
						// start at 1 and start the window
						$currentFailedCount = 1;
						$user->failed_password_attempt_window_start = DateTimeHelper::currentTime();
						$user->failed_password_attempt_count = $currentFailedCount;
					}
					else
					{
						$currentFailedCount += 1;
						$user->failed_password_attempt_count = $currentFailedCount;

						// they've reached the max number of failed password entries
						if ((int)$currentFailedCount >= (int)Blocks::app()->config->getItem('maxInvalidPasswordAttempts'))
						{
							// see if they are still inside the window
							if ($user->failed_password_attempt_window_start + ConfigHelper::getTimeInSeconds(Blocks::app()->config->getItem('failedPasswordWindow')) >= DateTimeHelper::currentTime())
							{
								if (Blocks::app()->config->getItem('failedPasswordMode') == FailedPasswordMode::Lockout)
								{
									$user->status = UserAccountStatus::Locked;
									$user->last_lockout_date = DateTimeHelper::currentTime();
									$user->failed_password_attempt_count = null;
									$user->failed_password_attempt_window_start = null;
								}
							}
						}
					}

					$user->save();
				}
				else
				{
					$this->_id = $user->id;
					$this->username = $user->username;
					$this->errorCode = self::ERROR_NONE;

					$authSessionToken = crypt(uniqid(rand(), true));
					$this->_authToken = $authSessionToken;
					$user->auth_session_token = $authSessionToken;
					$user->last_login_date = DateTimeHelper::currentTime();
					$user->failed_password_attempt_count = null;
					$user->failed_password_attempt_window_start = null;

					if (!$user->save())
					{
						$errorMsg = '';
						foreach ($user->errors as $errorArr)
							$errorMsg .= implode(' ', $errorArr);

						throw new Exception('There was a problem logging you in:'.$errorMsg);
					}

					$this->setState('authSessionToken', $authSessionToken);
				}
			}
		}

		return !$this->errorCode;
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * @return mixed
	 */
	public function getModel()
	{
		return $this->_model;
	}
}
