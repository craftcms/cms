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
			$this->errorCode = self::ERROR_USERNAME_INVALID;
		else
			$this->_processUserStatus($user);

		return !$this->errorCode;
	}

	/**
	 * @param User $user
	 */
	private function _processUserStatus(User $user)
	{
		switch ($user->status)
		{
			// If the account is pending, they don't exist yet.
			case UserAccountStatus::Pending:
			case UserAccountStatus::Archived:
			{
				$this->errorCode = self::ERROR_USERNAME_INVALID;
				break;
			}

			// if the account is locked, don't even attempt to log in.
			case UserAccountStatus::Locked:
			{
				if ($user->cooldown_start !== null)
				{
					// they are still in the cooldown window.
					if ($user->cooldown_start + ConfigHelper::getTimeInSeconds(blx()->config->failedPasswordCooldown) > DateTimeHelper::currentTime())
						$this->errorCode = self::ERROR_ACCOUNT_COOLDOWN;
					else
					{
						// no longer in cooldown window, set them to active and retry.
						$user->status = UserAccountStatus::Active;
						$user->cooldown_start = null;
						$user->save();
						$this->_processUserStatus($user);
					}
				}
				else
				{
					$this->errorCode = self::ERROR_ACCOUNT_LOCKED;
				}

				break;
			}

			// if the account is suspended don't attempt to log in.
			case UserAccountStatus::Suspended:
			{
				$this->errorCode = self::ERROR_ACCOUNT_SUSPENDED;
				break;
			}

			// account is active
			case UserAccountStatus::Active:
			{
				// check the password
				$checkPassword = blx()->security->checkPassword($this->password, $user->password, $user->enc_type);

				// bad password
				if (!$checkPassword)
				{
					$this->_processBadPassword($user);
				}
				else
				{
					// valid creds, but they have to reset their password.
					if ($user->password_reset_required == 1)
					{
						$this->_id = $user->id;
						$this->errorCode = self::ERROR_PASSWORD_RESET_REQUIRED;
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
	 * @param User $user
	 * @throws Exception
	 */
	private function _processSuccessfulLogin(User $user)
	{
		$this->_id = $user->id;
		$this->username = $user->username;
		$this->errorCode = self::ERROR_NONE;

		$authSessionToken = StringHelper::UUID();
		$user->auth_session_token = $authSessionToken;
		$user->last_login_date = DateTimeHelper::currentTime();
		$user->failed_password_attempt_count = null;
		$user->failed_password_attempt_window_start = null;
		$user->verification_code = null;
		$user->verification_code_issued_date = null;
		$user->verification_code_expiry_date = null;

		if (!$user->save())
		{
			$errorMsg = '';
			foreach ($user->errors as $errorArr)
				$errorMsg .= implode(' ', $errorArr);

			throw new Exception(Blocks::t('There was a problem logging you in: {errorMessage}', array('errorMessage' => $errorMsg)));
		}

		$this->setState('authSessionToken', $authSessionToken);
	}

	/**
	 * @param User $user
	 */
	private function _processBadPassword(User $user)
	{
		$this->errorCode = self::ERROR_PASSWORD_INVALID;
		$user->last_login_failed_date = DateTimeHelper::currentTime();

		// get the current failed password attempt count.
		$currentFailedCount = $user->failed_password_attempt_count;

		// if it's empty, this is the first failed attempt we have for the current window.
		if (StringHelper::isNullOrEmpty($currentFailedCount))
		{
			// start at 1 and start the window
			$currentFailedCount = 0;
			$user->failed_password_attempt_window_start = DateTimeHelper::currentTime();
		}

		$currentFailedCount += 1;
		$user->failed_password_attempt_count = $currentFailedCount;
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
					$this->errorCode = self::ERROR_ACCOUNT_COOLDOWN;
					$user->cooldown_start = DateTimeHelper::currentTime();
				}
				else
					$this->errorCode = self::ERROR_ACCOUNT_LOCKED;

				$user->status = UserAccountStatus::Locked;
				$user->last_lockout_date = DateTimeHelper::currentTime();
				$user->failed_password_attempt_count = null;
				$this->failedPasswordAttemptCount = 0;
				$user->failed_password_attempt_window_start = null;
			}
		}
		// the user is outside the window of failure, so we can reset their counters.
		else
		{
			$user->failed_password_attempt_count = 1;
			$this->failedPasswordAttemptCount = 1;
			$user->failed_password_attempt_window_start = DateTimeHelper::currentTime();
		}

		$user->save();
	}


	/**
	 * @param User $user
	 * @return bool
	 */
	private function _isUserInsideFailWindow(User $user)
	{
		$result = false;

		// check to see if the failed window start plus the configured failed password window is greater than the current time.
		$totalWindowTime = $user->failed_password_attempt_window_start + ConfigHelper::getTimeInSeconds(blx()->config->failedPasswordWindow);
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
