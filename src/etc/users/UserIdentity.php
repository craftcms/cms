<?php
namespace Craft;

/**
 * UserIdentity represents the data needed to identify a user. It contains the authentication method that checks if the
 * provided data can identity the user.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.users
 * @since     1.0
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
	const ERROR_PENDING_VERIFICATION    = 56;
	const ERROR_NO_SITE_OFFLINE_ACCESS  = 57;

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

		$this->_userModel = craft()->users->getUserByUsernameOrEmail($username);
	}

	/**
	 * Authenticates a user against the database.
	 *
	 * @return bool true, if authentication succeeds, false otherwise.
	 */
	public function authenticate()
	{
		$user = craft()->users->getUserByUsernameOrEmail($this->username);

		if ($user)
		{
			// Add a little randomness in the timing of the response.
			$this->_slowRoll();
			return $this->_processUserStatus($user);
		}
		else
		{
			// Spin some cycles validating a random password hash.
			craft()->users->validatePassword('$2y$13$L.NLoP5bLzBTP66WendST.4uKn4CTz7ngo9XzVDCfv8yfdME7NEwa', $this->password);

			// Add a little randomness in the timing of the response.
			$this->_slowRoll();

			$this->errorCode = static::ERROR_PASSWORD_INVALID;
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
			case UserStatus::Archived:
			{
				$this->errorCode = static::ERROR_USERNAME_INVALID;
				break;
			}

			case UserStatus::Locked:
			{
				// Let them know how much time they have to wait (if any) before their account is unlocked.
				$this->errorCode = $this->_getLockedAccountErrorCode();

				break;
			}

			case UserStatus::Suspended:
			{
				$this->errorCode = static::ERROR_ACCOUNT_SUSPENDED;
				break;
			}

			case UserStatus::Pending:
			{
				$this->errorCode = static::ERROR_PENDING_VERIFICATION;
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
						craft()->users->sendPasswordResetEmail($user);
					}
					else if (craft()->request->isCpRequest() && !$user->can('accessCp'))
					{
						$this->errorCode = static::ERROR_NO_CP_ACCESS;
					}
					else if (craft()->request->isCpRequest() && !craft()->isSystemOn() && !$user->can('accessCpWhenSystemIsOff'))
					{
						$this->errorCode = static::ERROR_NO_CP_OFFLINE_ACCESS;
					}
					else if (craft()->request->isSiteRequest() && !craft()->isSystemOn() && !$user->can('accessSiteWhenSystemIsOff'))
					{
						$this->errorCode = static::ERROR_NO_SITE_OFFLINE_ACCESS;
					}
					else
					{
						// Everything is good.
						$this->errorCode = static::ERROR_NONE;
					}
				}
				else
				{
					craft()->users->handleInvalidLogin($user);

					$this->errorCode = static::ERROR_PASSWORD_INVALID;
				}
				break;
			}

			default:
			{
				throw new Exception(Craft::t('User has unknown status “{status}”', array($user->status)));
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
		if (craft()->config->get('cooldownDuration'))
		{
			return static::ERROR_ACCOUNT_COOLDOWN;
		}
		else
		{
			return static::ERROR_ACCOUNT_LOCKED;
		}
	}

	/**
	 * Introduces a random delay into the script to help prevent timing enumeration attacks.
	 */
	private function _slowRoll()
	{
		// Delay randomly between 0 and 1.5 seconds.
		usleep(mt_rand(0, 1500000));
	}
}
