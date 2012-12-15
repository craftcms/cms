<?php
namespace Blocks;

/**
 * UserIdentity represents the data needed to identify a user.
 * It contains the authentication method that checks if the provided data can identity the user.
 */
class UserIdentity extends \CUserIdentity
{
	private $_id;

	const ERROR_ACCOUNT_LOCKED          = 50;
	const ERROR_ACCOUNT_COOLDOWN        = 51;
	const ERROR_PASSWORD_RESET_REQUIRED = 52;
	const ERROR_ACCOUNT_SUSPENDED       = 53;
	const ERROR_NO_CP_ACCESS            = 54;

	/**
	 * Authenticates a user against the database.
	 *
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{
		$user = blx()->accounts->getUserByUsernameOrEmail($this->username);

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
				if (blx()->security->checkPassword($this->password, $user->password, $user->encType))
				{
					if ($user->passwordResetRequired)
					{
						$this->_id = $user->id;
						$this->errorCode = static::ERROR_PASSWORD_RESET_REQUIRED;
						blx()->accounts->sendForgotPasswordEmail($user);
					}
					else if (blx()->request->isCpRequest() && !$user->can('accessCp'))
					{
						$this->errorCode = static::ERROR_NO_CP_ACCESS;
					}
					else
					{
						// Finally, everything is well with the world. Let's log in.
						$this->_id = $user->id;
						$this->username = $user->username;
						$this->errorCode = static::ERROR_NONE;

						$authSessionToken = StringHelper::UUID();
						blx()->accounts->handleSuccessfulLogin($user, $authSessionToken);
						$this->setState('authSessionToken', $authSessionToken);
					}
				}
				else
				{
					blx()->accounts->handleInvalidLogin($user);

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
				throw new Exception(Blocks::t('User has unknown status “{status}”', array($user->status)));
			}
		}
	}

	/**
	 * Returns the proper Account Locked error code, based on the system's Invalid Login Mode
	 *
	 * @access private
	 * @return int
	 */
	private function _getLockedAccountErrorCode()
	{
		if (blx()->config->get('cooldownDuration'))
		{
			return static::ERROR_ACCOUNT_COOLDOWN;
		}
		else
		{
			return static::ERROR_ACCOUNT_LOCKED;
		}
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->_id;
	}
}
