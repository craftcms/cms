<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\users;

use craft\app\Craft;
use craft\app\enums\UserStatus;
use craft\app\errors\Exception;
use craft\app\models\User       as UserModel;

/**
 * UserIdentity represents the data needed to identify a user. It contains the authentication method that checks if the
 * provided data can identity the user.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserIdentity extends \CUserIdentity
{
	// Constants
	// =========================================================================

	const ERROR_ACCOUNT_LOCKED          = 50;
	const ERROR_ACCOUNT_COOLDOWN        = 51;
	const ERROR_PASSWORD_RESET_REQUIRED = 52;
	const ERROR_ACCOUNT_SUSPENDED       = 53;
	const ERROR_NO_CP_ACCESS            = 54;
	const ERROR_NO_CP_OFFLINE_ACCESS    = 55;

	// Properties
	// =========================================================================

	/**
	 * @var int
	 */
	private $_id;

	/**
	 * @var UserModel
	 */
	private $_userModel;

	// Public Methods
	// =========================================================================


	/**
	 * UserIdentity constructor.
	 *
	 * @param string $username username
	 * @param string $password password
	 */
	public function __construct($username,$password)
	{
		$this->username = $username;
		$this->password = $password;

		$this->_userModel = Craft::$app->users->getUserByUsernameOrEmail($username);
	}

	/**
	 * Authenticates a user against the database.
	 *
	 * @return bool true, if authentication succeeds, false otherwise.
	 */
	public function authenticate()
	{
		$user = Craft::$app->users->getUserByUsernameOrEmail($this->username);

		if ($user)
		{
			return $this->_processUserStatus($user);
		}
		else
		{
			$this->errorCode = static::ERROR_USERNAME_INVALID;
			return false;
		}
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * @return UserModel
	 */
	public function getUserModel()
	{
		return $this->_userModel;
	}

	/**
	 * @param $user
	 *
	 * @return null
	 */
	public function logUserIn($user)
	{
		$this->_id = $user->id;
		$this->username = $user->username;
		$this->errorCode = static::ERROR_NONE;
		$this->_userModel = $user;
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param UserModel $user
	 *
	 * @throws Exception
	 * @return null
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
				if (Craft::$app->users->validatePassword($user->password, $this->password))
				{
					if ($user->passwordResetRequired)
					{
						$this->_id = $user->id;
						$this->errorCode = static::ERROR_PASSWORD_RESET_REQUIRED;
						Craft::$app->users->sendPasswordResetEmail($user);
					}
					else if (Craft::$app->request->isCpRequest() && !$user->can('accessCp'))
					{
						$this->errorCode = static::ERROR_NO_CP_ACCESS;
					}
					else if (Craft::$app->request->isCpRequest() && !Craft::$app->isSystemOn() && !$user->can('accessCpWhenSystemIsOff'))
					{
						$this->errorCode = static::ERROR_NO_CP_OFFLINE_ACCESS;
					}
					else
					{
						// Everything is good.
						$this->errorCode = static::ERROR_NONE;
					}
				}
				else
				{
					Craft::$app->users->handleInvalidLogin($user);

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
				throw new Exception(Craft::t('User has unknown status “{status}”', [$user->status]));
			}
		}

		return $this->errorCode === static::ERROR_NONE;
	}

	/**
	 * Returns the proper Account Locked error code, based on the system's
	 * Invalid Login Mode
	 *
	 * @return int
	 */
	private function _getLockedAccountErrorCode()
	{
		if (Craft::$app->config->get('cooldownDuration'))
		{
			return static::ERROR_ACCOUNT_COOLDOWN;
		}
		else
		{
			return static::ERROR_ACCOUNT_LOCKED;
		}
	}
}
