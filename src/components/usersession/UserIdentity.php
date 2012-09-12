<?php
namespace Blocks;

/**
 * UserIdentity represents the data needed to identify a user.
 * It contains the authentication method that checks if the provided data can identity the user.
 */
class UserIdentity extends \CUserIdentity
{
	private $_id;

	public $failedPasswordAttemptCount;

	const ERROR_ACCOUNT_LOCKED          = 50;
	const ERROR_ACCOUNT_COOLDOWN        = 51;
	const ERROR_PASSWORD_RESET_REQUIRED = 52;
	const ERROR_ACCOUNT_SUSPENDED       = 53;

	/**
	 * Authenticates a user against the database.
	 *
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{
		$user = blx()->accounts->getUserByUsernameOrEmail($this->username);

		if ($user === null)
			$this->errorCode = static::ERROR_USERNAME_INVALID;
		else
			$this->_processUserStatus($user);

		return !$this->errorCode;
	}

	/**
	 * @param UserRecord $user
	 */
	private function _processUserStatus(UserRecord $user)
	{
		switch ($user->status)
		{
			// If the account is pending, they don't exist yet.
			case UserAccountStatus::Pending:
			case UserAccountStatus::Archived:
			{
				$this->errorCode = static::ERROR_USERNAME_INVALID;
				break;
			}

			// if the account is locked, don't even attempt to log in.
			case UserAccountStatus::Locked:
			{
				if ($user->cooldownStart !== null)
				{
					// they are still in the cooldown window.
					if ($user->cooldownStart + ConfigHelper::getTimeInSeconds(blx()->config->failedPasswordCooldown) > DateTimeHelper::currentTime())
						$this->errorCode = static::ERROR_ACCOUNT_COOLDOWN;
					else
					{
						// no longer in cooldown window, set them to active and retry.
						$user->status = UserAccountStatus::Active;
						$user->cooldownStart = null;
						$user->save();
						$this->_processUserStatus($user);
					}
				}
				else
				{
					$this->errorCode = static::ERROR_ACCOUNT_LOCKED;
				}

				break;
			}

			// if the account is suspended don't attempt to log in.
			case UserAccountStatus::Suspended:
			{
				$this->errorCode = static::ERROR_ACCOUNT_SUSPENDED;
				break;
			}

			// account is active
			case UserAccountStatus::Active:
			{
				// check the password
				$checkPassword = blx()->security->checkPassword($this->password, $user->password, $user->encType);

				// bad password
				if (!$checkPassword)
				{
					$this->_processBadPassword($user);
				}
				else
				{
					// valid creds, but they have to reset their password.
					if ($user->passwordResetRequired)
					{
						$this->_id = $user->id;
						$this->errorCode = static::ERROR_PASSWORD_RESET_REQUIRED;
						blx()->accounts->forgotPassword($user);
					}
					else
					{
						// finally, everything is well with the world.  let's log in.
						$this->_processSuccessfulLogin($user);
					}
				}
				break;
			}
		}
	}

	/**
	 * @param UserRecord $user
	 * @throws Exception
	 */
	private function _processSuccessfulLogin(UserRecord $user)
	{
		$this->_id = $user->id;
		$this->username = $user->username;
		$this->errorCode = static::ERROR_NONE;

		$authSessionToken = StringHelper::UUID();
		$user->authSessionToken = $authSessionToken;
		$user->lastLoginDate = DateTimeHelper::currentTime();
		$user->failedPasswordAttemptCount = null;
		$user->failedPasswordAttemptWindowStart = null;
		$user->verificationCode = null;
		$user->verificationCodeIssuedDate = null;
		$user->verificationCodeExpiryDate = null;
		$user->lastLoginAttemptIPAddress = blx()->request->getUserHostAddress();

		if (!$user->save())
		{
			$errorMsg = '';
			foreach ($user->errors as $errorArr)
				$errorMsg .= implode(' ', $errorArr);

			throw new Exception(Blocks::t('There was a problem logging you in: {error}', array('error' => $errorMsg)));
		}

		$this->setState('authSessionToken', $authSessionToken);
	}

	/**
	 * @param UserRecord $user
	 */
	private function _processBadPassword(UserRecord $user)
	{
		$this->errorCode = static::ERROR_PASSWORD_INVALID;
		$user->lastLoginFailedDate = DateTimeHelper::currentTime();
		$user->lastLoginAttemptIPAddress = blx()->request->getUserHostAddress();

		// get the current failed password attempt count.
		$currentFailedCount = $user->failedPasswordAttemptCount;

		// if it's empty, this is the first failed attempt we have for the current window.
		if (StringHelper::isNullOrEmpty($currentFailedCount))
		{
			// start at 1 and start the window
			$currentFailedCount = 0;
			$user->failedPasswordAttemptWindowStart = DateTimeHelper::currentTime();
		}

		$currentFailedCount += 1;
		$user->failedPasswordAttemptCount = $currentFailedCount;
		$this->failedPasswordAttemptCount = $currentFailedCount;

		// check to see if they are still inside the configured failure window
		if ($this->_isUserInsideFailWindow($user, $currentFailedCount))
		{
			// check to see if they hit the max attempts to login.
			if ($currentFailedCount >= blx()->config->maxInvalidPasswordAttempts)
			{
				// time to slow things down a bit.
				if (blx()->config->failedPasswordMode === FailedPasswordMode::Cooldown)
				{
					$this->errorCode = static::ERROR_ACCOUNT_COOLDOWN;
					$user->cooldownStart = DateTimeHelper::currentTime();
				}
				else
					$this->errorCode = static::ERROR_ACCOUNT_LOCKED;

				$user->status = UserAccountStatus::Locked;
				$user->lastLockoutDate = DateTimeHelper::currentTime();
				$user->failedPasswordAttemptCount = null;
				$this->failedPasswordAttemptCount = 0;
				$user->failedPasswordAttemptWindowStart = null;
			}
		}
		// the user is outside the window of failure, so we can reset their counters.
		else
		{
			$user->failedPasswordAttemptCount = 1;
			$this->failedPasswordAttemptCount = 1;
			$user->failedPasswordAttemptWindowStart = DateTimeHelper::currentTime();
		}

		$user->save();
	}


	/**
	 * @param UserRecord $user
	 * @return bool
	 */
	private function _isUserInsideFailWindow(UserRecord $user)
	{
		$result = false;

		// check to see if the failed window start plus the configured failed password window is greater than the current time.
		$totalWindowTime = $user->failedPasswordAttemptWindowStart + ConfigHelper::getTimeInSeconds(blx()->config->failedPasswordWindow);
		$currentTime = DateTimeHelper::currentTime();
		if ($currentTime < $totalWindowTime)
			$result = true;

		return $result;
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->_id;
	}
}
