<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\Craft;
use craft\app\dates\DateInterval;
use craft\app\dates\DateTime;
use craft\app\enums\AttributeType;
use craft\app\enums\AuthError;
use craft\app\enums\ElementType;
use craft\app\enums\UserStatus;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\UrlHelper;
use craft\app\models\UserGroup        as UserGroupModel;
use craft\app\models\User             as UserModel;
use yii\web\IdentityInterface;
use yii\base\NotSupportedException;

/**
 * User model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class User extends BaseElementModel implements IdentityInterface
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	protected $elementType = ElementType::User;

	/**
	 * The cached list of groups the user belongs to. Set by [[getGroups()]].
	 *
	 * @var array
	 */
	private $_groups;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IdentityInterface::findIdentity()
	 *
	 * @param string|int $id
	 * @return IdentityInterface|null
	 */
	public static function findIdentity($id)
	{
		$user = craft()->users->getUserById($id);

		if ($user->status == UserStatus::Active)
		{
			return $user;
		}
	}

	/**
	 * @inheritDoc IdentityInterface::findIdentityByAccessToken()
	 *
	 * @param mixed $token
	 * @param mixed $type
	 * @return IdentityInterface|null
	 */
	public static function findIdentityByAccessToken($token, $type = null)
	{
		throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
	}

	/**
	 * Use the full name or username as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		if (craft()->config->get('useEmailAsUsername'))
		{
			return $this->email;
		}
		else
		{
			return $this->username;
		}
	}

	/**
	 * @inheritDoc IdentityInterface::getAuthKey()
	 *
	 * @return string|null
	 * @see validateAuthKey()
	 */
	public function getAuthKey()
	{
		return $this->getAttribute('authKey');
	}

	/**
	 * @inheritDoc IdentityInterface::validateAuthKey()
	 *
	 * @param string $authKey
	 * @return boolean
	 * @see getAuthKey()
	 */
	public function validateAuthKey($authKey)
	{
		return ($this->getAuthKey() === $authKey);
	}

	/**
	 * Determines whether the user is allowed to be logged in with a given password.
	 *
	 * @param string $password The user's plain text passwerd.
	 *
	 * @return bool
	 */
	public function authenticate($password)
	{
		switch ($this->status)
		{
			case UserStatus::Archived:
			{
				$this->authError = AuthError::InvalidCredentials;
				return false;
			}

			case UserStatus::Pending:
			{
				$this->authError = AuthError::PendingVerification;
				return false;
			}

			case UserStatus::Suspended:
			{
				$this->errorCode = AuthError::AccountSuspended;
				return false;
			}

			case UserStatus::Locked:
			{
				if (craft()->config->get('cooldownDuration'))
				{
					$this->authError = AuthError::AccountCooldown;
				}
				else
				{
					$this->authError = AuthError::AccountLocked;
				}
				return false;
			}

			case UserStatus::Active:
			{
				// Validate the password
				if (!craft()->getSecurity()->validatePassword($password, $this->password))
				{
					craft()->users->handleInvalidLogin($this);

					// Was that one bad password too many?
					if ($this->status == UserStatus::Locked)
					{
						// Will set the authError to either AccountCooldown or AccountLocked
						return $this->authenticate($password);
					}
					else
					{
						$this->authError = AuthError::InvalidCredentials;
						return false;
					}
				}

				// Is a password reset required?
				if ($this->passwordResetRequired)
				{
					$this->authError = AuthError::PasswordResetRequired;
					return false;
				}

				if (craft()->request->isCpRequest())
				{
					if (!$this->can('accessCp'))
					{
						$this->authError = AuthError::NoCpAccess;
						return false;
					}

					if (!craft()->isSystemOn() && !$this->can('accessCpWhenSystemIsOff'))
					{
						$this->authError = AuthError::NoCpOfflineAccess;
						return false;
					}
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the reference string to this element.
	 *
	 * @return string|null
	 */
	public function getRef()
	{
		return $this->username;
	}

	/**
	 * Returns the user's groups.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getGroups($indexBy = null)
	{
		if (!isset($this->_groups))
		{
			if (craft()->getEdition() == Craft::Pro)
			{
				$this->_groups = craft()->userGroups->getGroupsByUserId($this->id);
			}
			else
			{
				$this->_groups = array();
			}
		}

		if (!$indexBy)
		{
			$groups = $this->_groups;
		}
		else
		{
			$groups = array();

			foreach ($this->_groups as $group)
			{
				$groups[$group->$indexBy] = $group;
			}
		}

		return $groups;
	}

	/**
	 * Returns whether the user is in a specific group.
	 *
	 * @param mixed $group The user group model, its handle, or ID.
	 *
	 * @return bool
	 */
	public function isInGroup($group)
	{
		if (craft()->getEdition() == Craft::Pro)
		{
			if (is_object($group) && $group instanceof UserGroupModel)
			{
				$group = $group->id;
			}

			if (is_numeric($group))
			{
				$groups = array_keys($this->getGroups('id'));
			}
			else if (is_string($group))
			{
				$groups = array_keys($this->getGroups('handle'));
			}

			if (!empty($groups))
			{
				return in_array($group, $groups);
			}
		}

		return false;
	}

	/**
	 * Gets the user's full name.
	 *
	 * @return string|null
	 */
	public function getFullName()
	{
		$firstName = trim($this->firstName);
		$lastName = trim($this->lastName);

		return $firstName.($firstName && $lastName ? ' ' : '').$lastName;
	}

	/**
	 * Returns the user's full name or username.
	 *
	 * @return string
	 */
	public function getName()
	{
		$fullName = $this->getFullName();

		if ($fullName)
		{
			return $fullName;
		}
		else
		{
			return $this->username;
		}
	}

	/**
	 * Gets the user's first name or username.
	 *
	 * @return string|null
	 */
	public function getFriendlyName()
	{
		if ($firstName = trim($this->firstName))
		{
			return $firstName;
		}
		else
		{
			return $this->username;
		}
	}

	/**
	 * @inheritDoc BaseElementModel::getStatus()
	 *
	 * @return string|null
	 */
	public function getStatus()
	{
		if ($this->locked)
		{
			return UserStatus::Locked;
		}

		if ($this->suspended)
		{
			return UserStatus::Suspended;
		}

		if ($this->archived)
		{
			return UserStatus::Archived;
		}

		if ($this->pending)
		{
			return UserStatus::Pending;
		}

		return UserStatus::Active;
	}

	/**
	 * Sets a user's status to active.
	 */
	public function setActive()
	{
		$this->pending = false;
		$this->locked = false;
		$this->suspended = false;
		$this->archived = false;
	}

	/**
	 * Returns the URL to the user's photo.
	 *
	 * @param int $size
	 *
	 * @return string|null
	 */
	public function getPhotoUrl($size = 100)
	{
		if ($this->photo)
		{
			return UrlHelper::getResourceUrl('userphotos/'.$this->username.'/'.$size.'/'.$this->photo);
		}
	}

	/**
	 * @inheritDoc BaseElementModel::getThumbUrl()
	 *
	 * @param int $size
	 *
	 * @return false|null|string
	 */
	public function getThumbUrl($size = 100)
	{
		$url = $this->getPhotoUrl($size);
		if (!$url)
		{
			$url = UrlHelper::getResourceUrl('defaultuserphoto/'.$size);
		}

		return $url;
	}

	/**
	 * @inheritDoc BaseElementModel::isEditable()
	 *
	 * @return bool
	 */
	public function isEditable()
	{
		return craft()->getUser()->checkPermission('editUsers');
	}

	/**
	 * Returns whether this is the current logged-in user.
	 *
	 * @return bool
	 */
	public function isCurrent()
	{
		if ($this->id)
		{
			$currentUser = craft()->getUser()->getIdentity();

			if ($currentUser)
			{
				return ($this->id == $currentUser->id);
			}
		}

		return false;
	}

	/**
	 * Returns whether the user has permission to perform a given action.
	 *
	 * @param string $permission
	 *
	 * @return bool
	 */
	public function can($permission)
	{
		if (craft()->getEdition() == Craft::Pro)
		{
			if ($this->admin || $this->client)
			{
				return true;
			}
			else if ($this->id)
			{
				return craft()->userPermissions->doesUserHavePermission($this->id, $permission);
			}
			else
			{
				return false;
			}
		}
		else
		{
			return true;
		}
	}

	/**
	 * Returns whether the user has shunned a given message.
	 *
	 * @param string $message
	 *
	 * @return bool
	 */
	public function hasShunned($message)
	{
		if ($this->id)
		{
			return craft()->users->hasUserShunnedMessage($this->id, $message);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns the time when the user will be over their cooldown period.
	 *
	 * @return DateTime|null
	 */
	public function getCooldownEndTime()
	{
		if ($this->status == UserStatus::Locked)
		{
			$cooldownEnd = clone $this->lockoutDate;
			$cooldownEnd->add(new DateInterval(craft()->config->get('cooldownDuration')));

			return $cooldownEnd;
		}
	}

	/**
	 * Returns the remaining cooldown time for this user, if they've entered their password incorrectly too many times.
	 *
	 * @return DateInterval|null
	 */
	public function getRemainingCooldownTime()
	{
		if ($this->status == UserStatus::Locked)
		{
			$currentTime = DateTimeHelper::currentUTCDateTime();
			$cooldownEnd = $this->getCooldownEndTime();

			if ($currentTime < $cooldownEnd)
			{
				return $currentTime->diff($cooldownEnd);
			}
		}
	}

	/**
	 * @inheritDoc BaseElementModel::getCpEditUrl()
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		if ($this->isCurrent())
		{
			return UrlHelper::getCpUrl('myaccount');
		}
		else if (craft()->getEdition() == Craft::Client && $this->client)
		{
			return UrlHelper::getCpUrl('clientaccount');
		}
		else if (craft()->getEdition() == Craft::Pro)
		{
			return UrlHelper::getCpUrl('users/'.$this->id);
		}
		else
		{
			return false;
		}
	}

	/**
	 * @inheritDoc BaseModel::populateModel()
	 *
	 * @param mixed $attributes
	 *
	 * @return UserModel
	 */
	public static function populateModel($attributes)
	{
		$user = parent::populateModel($attributes);

		// Is the user in cooldown mode, and are they past their window?
		if ($user->status == UserStatus::Locked)
		{
			$cooldownDuration = craft()->config->get('cooldownDuration');

			if ($cooldownDuration)
			{
				if (!$user->getRemainingCooldownTime())
				{
					craft()->users->activateUser($user);
				}
			}
		}

		return $user;
	}

	/**
	 * Validates all of the attributes for the current Model. Any attributes that fail validation will additionally get
	 * logged to the `craft/storage/runtime/logs` folder with a level of LogLevel::Warning.
	 *
	 * In addition, we check that the username does not have any whitespace in it.
	 *
	 * @param null $attributes
	 * @param bool $clearErrors
	 *
	 * @return bool|null
	 */
	public function validate($attributes = null, $clearErrors = true)
	{
		// Don't allow whitespace in the username.
		if (preg_match('/\s+/', $this->username))
		{
			$this->addError('username', Craft::t('Spaces are not allowed in the username.'));
		}

		return parent::validate($attributes, false);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		$requireUsername = !craft()->config->get('useEmailAsUsername');

		return array_merge(parent::defineAttributes(), [
			'authKey'                    => AttributeType::String,
			'username'                   => [AttributeType::String, 'maxLength' => 100, 'required' => $requireUsername],
			'photo'                      => AttributeType::String,
			'firstName'                  => AttributeType::String,
			'lastName'                   => AttributeType::String,
			'email'                      => [AttributeType::Email, 'required' => !$requireUsername],
			'password'                   => AttributeType::String,
			'preferredLocale'            => AttributeType::Locale,
			'weekStartDay'               => [AttributeType::Number, 'default' => 0],
			'admin'                      => AttributeType::Bool,
			'client'                     => AttributeType::Bool,
			'locked'                     => AttributeType::Bool,
			'suspended'                  => AttributeType::Bool,
			'pending'                    => AttributeType::Bool,
			'archived'                   => AttributeType::Bool,
			'lastLoginDate'              => AttributeType::DateTime,
			'invalidLoginCount'          => AttributeType::Number,
			'lastInvalidLoginDate'       => AttributeType::DateTime,
			'lockoutDate'                => AttributeType::DateTime,
			'passwordResetRequired'      => AttributeType::Bool,
			'lastPasswordChangeDate'     => AttributeType::DateTime,
			'unverifiedEmail'            => AttributeType::Email,
			'newPassword'                => AttributeType::String,
			'currentPassword'            => AttributeType::String,
			'verificationCodeIssuedDate' => AttributeType::DateTime,
			'authError'                  => AttributeType::String,
		]);
	}
}
