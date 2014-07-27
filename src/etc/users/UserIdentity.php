<?php
namespace Craft;

/**
 * UserIdentity represents the data needed to identify a user.
 * It contains the authentication method that checks if the provided data can identity the user.
 *
 * @package craft.app.etc.users
 */
class UserIdentity extends \CUserIdentity
{
	private $_id;

	const ERROR_ACCOUNT_LOCKED          = 50;
	const ERROR_ACCOUNT_COOLDOWN        = 51;
	const ERROR_PASSWORD_RESET_REQUIRED = 52;
	const ERROR_ACCOUNT_SUSPENDED       = 53;
	const ERROR_NO_CP_ACCESS            = 54;
	const ERROR_NO_CP_OFFLINE_ACCESS    = 55;

	/**
	 * Authenticates a user against the database.
	 *
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{
		$user = craft()->users->getUserByUsernameOrEmail($this->username);

		if ($user)
		{
			$this->_processUserStatus($user);
			return true;
		}
		else
		{
			$this->errorCode = static::ERROR_USERNAME_INVALID;
			return false;
		}
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * @access private
	 * @param UserModel $user
	 * @throws Exception
	 * @return void
	 */
	private function _processUserStatus(UserModel $user)
	{
		switch ($user->status)
		{
			// If the account is pending, they don't exist yet.
			case UserStatus::Pending:
			case UserStatus::Archived:
			{
				$this->errorCode = static::ERROR_USERNAME_INVALID;
				break;
			}

			case UserStatus::Locked:
			{
				$this->errorCode = $this->_getLockedAccountErrorCode();
				break;
			}

			case UserStatus::Suspended:
			{
				$this->errorCode = static::ERROR_ACCOUNT_SUSPENDED;
				break;
			}

			case UserStatus::Active:
			{
				// Validate the password
				if (craft()->users->validatePassword($user->password, $this->password))
				{
					if ($user->passwordResetRequired)
					{
						$this->_id = $user->id;
						$this->errorCode = static::ERROR_PASSWORD_RESET_REQUIRED;
						craft()->users->sendForgotPasswordEmail($user);
					}
					else if (craft()->request->isCpRequest() && !$user->can('accessCp'))
					{
						$this->errorCode = static::ERROR_NO_CP_ACCESS;
					}
					else if (craft()->request->isCpRequest() && !craft()->isSystemOn() && !$user->can('accessCpWhenSystemIsOff'))
					{
						$this->errorCode = static::ERROR_NO_CP_OFFLINE_ACCESS;
					}
					else
					{
						// Finally, everything is well with the world. Let's log in.
						$this->logUserIn($user);
					}
				}
				else
				{
					craft()->users->handleInvalidLogin($user);

					// Was that one bad password too many?
					if ($user->status == UserStatus::Locked)
					{
						$this->errorCode = $this->_getLockedAccountErrorCode();
					}
					else
					{
						$this->errorCode = static::ERROR_PASSWORD_INVALID;
					}
				}
				break;
			}

			default:
			{
				throw new Exception(Craft::t('User has unknown status “{status}”', array($user->status)));
			}
		}
	}

	/**
	 * @param $user
	 */
	public function logUserIn($user)
	{
		$this->_id = $user->id;
		$this->username = $user->username;
		$this->errorCode = static::ERROR_NONE;
	}

	/**
	 * Returns the proper Account Locked error code, based on the system's Invalid Login Mode
	 *
	 * @access private
	 * @return int
	 */
	private function _getLockedAccountErrorCode()
	{
		if (craft()->config->get('cooldownDuration'))
		{
			return static::ERROR_ACCOUNT_COOLDOWN;
		}
		else
		{
			return static::ERROR_ACCOUNT_LOCKED;
		}
	}
}
