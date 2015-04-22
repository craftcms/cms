<?php
namespace Craft;

/**
 * User model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.0
 */
class UserModel extends BaseElementModel
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	protected $elementType = ElementType::User;

	/**
	 * The cached list of groups the user belongs to. Set by {@link getGroups()}.
	 *
	 * @var array
	 */
	private $_groups;

	// Public Methods
	// =========================================================================

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
		return craft()->userSession->checkPermission('editUsers');
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
			$currentUser = craft()->userSession->getUser();

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
			// There was an old bug that where a user's lockoutDate could be null if they've
			// passed their cooldownDuration already, but there account status is still locked.
			// If that's the case, just let it return null as if they are past the cooldownDuration.
			if ($this->lockoutDate)
			{
				$cooldownEnd = clone $this->lockoutDate;
				$cooldownEnd->add(new DateInterval(craft()->config->get('cooldownDuration')));

				return $cooldownEnd;
			}
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
					craft()->users->unlockUser($user);
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

		return array_merge(parent::defineAttributes(), array(
			'username'                   => array(AttributeType::String, 'maxLength' => 100, 'required' => $requireUsername),
			'photo'                      => array(AttributeType::String, 'maxLength' => 100),
			'firstName'                  => AttributeType::String,
			'lastName'                   => AttributeType::String,
			'email'                      => array(AttributeType::Email, 'required' => !$requireUsername),
			'password'                   => AttributeType::String,
			'preferredLocale'            => AttributeType::Locale,
			'weekStartDay'               => array(AttributeType::Number, 'default' => 0),
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
		));
	}
}
