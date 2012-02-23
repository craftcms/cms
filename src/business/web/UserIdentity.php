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

	public $cooldownTimeRemaining;

	public $loginName;
	public $password;

	const ERROR_ACCOUNT_LOCKED = 50;
	const ERROR_ACCOUNT_COOLDOWN = 51;

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
		{
			$this->errorCode = self::ERROR_USERNAME_INVALID;
		}
		else
		{
			// if the account is locked, don't even attempt to log in.
			if ($user->status == UserAccountStatus::Locked)
			{
				$this->errorCode = self::ERROR_ACCOUNT_LOCKED;
			}
			else
			{
				// if the account is in cooldown mode, don't attempt to log in.
				if ($this->_isUserOutsideFailWindow($user, $user->failed_password_attempt_count))
				{
					$this->errorCode = self::ERROR_ACCOUNT_COOLDOWN;
				}
				else
				{
					$checkPassword = Blocks::app()->security->checkPassword($this->password, $user->password, $user->enc_type);

					// bad password
					if (!$checkPassword)
					{
						$this->errorCode = self::ERROR_PASSWORD_INVALID;

						$user->last_login_failed_date = DateTimeHelper::currentTime();

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
							// If they have made it here, then they are outside of a previous fail window and they have mistyped their password again.  We start the counter back over at 0.
							if ($currentFailedCount >= Blocks::app()->config->getItem('maxInvalidPasswordAttempts'))
								$currentFailedCount = 0;

							$currentFailedCount += 1;
							$user->failed_password_attempt_count = $currentFailedCount;

							if ($this->_isUserOutsideFailWindow($user, $currentFailedCount))
							{
								if (Blocks::app()->config->getItem('failedPasswordMode') === FailedPasswordMode::Lockout)
								{
									$user->status = UserAccountStatus::Locked;
									$user->last_lockout_date = DateTimeHelper::currentTime();
									$user->failed_password_attempt_count = null;
									$user->failed_password_attempt_window_start = null;
								}
							}
						}

						$user->save();
					}
					else
					{
						// sucessfully authenticated
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
		}

		return !$this->errorCode;
	}

	/**
	 * @param User $user
	 * @param      $failedCount
	 * @return bool
	 */
	private function _isUserOutsideFailWindow(User $user, $failedCount)
	{
		$result = false;

		// check to see if the current failed count is greater than or equal to the max configured password attempt limit
		if ((int)$failedCount >= (int)Blocks::app()->config->getItem('maxInvalidPasswordAttempts'))
		{
			// check to see if the failed windows start plus the configured failed password window is greater than the current time.
			if ($user->failed_password_attempt_window_start + ConfigHelper::getTimeInSeconds(Blocks::app()->config->getItem('failedPasswordWindow')) >= DateTimeHelper::currentTime())
			{
				$cooldownEnd = $user->last_login_failed_date + ConfigHelper::getTimeInSeconds(Blocks::app()->config->getItem('failedPasswordCooldown'));
				$cooldownRemaining = $cooldownEnd - DateTimeHelper::currentTime();

				// we have one exception for if the last time they attempted and failed to login plus the failed password cooldown is less than the current time.
				if ($cooldownRemaining > 0)
				{
					$this->cooldownTimeRemaining = $cooldownRemaining;
					$result = true;
				}
				else
					$result = false;
			}
		}

		return $result;
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
