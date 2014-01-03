<?php
namespace Craft;

/**
 * User model class
 */
class UserModel extends BaseElementModel
{
	protected $elementType = ElementType::User;

	/**
	 * Use the full name or username as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->username;
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'username'               => array(AttributeType::String, 'maxLength' => 100, 'required' => true),
			'photo'                  => AttributeType::String,
			'firstName'              => AttributeType::String,
			'lastName'               => AttributeType::String,
			'email'                  => AttributeType::Email,
			'password'               => AttributeType::String,
			'preferredLocale'        => AttributeType::Locale,
			'admin'                  => AttributeType::Bool,
			'status'                 => AttributeType::Enum,
			'lastLoginDate'          => AttributeType::DateTime,
			'invalidLoginCount'      => AttributeType::Number,
			'lastInvalidLoginDate'   => AttributeType::DateTime,
			'lockoutDate'            => AttributeType::DateTime,
			'passwordResetRequired'  => AttributeType::Bool,
			'lastPasswordChangeDate' => AttributeType::DateTime,
			'verificationRequired'   => AttributeType::Bool,
			'newPassword'            => AttributeType::String,
			'currentPassword'        => AttributeType::String,
		));
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
	 * @return array
	 */
	public function getGroups($indexBy = null)
	{
		if (craft()->hasPackage(CraftPackage::Users))
		{
			return craft()->userGroups->getGroupsByUserId($this->id, $indexBy);
		}
		else
		{
			return array();
		}
	}

	/**
	 * Returns whether the user is in a specific group.
	 *
	 * @param mixed $group The user group model, its handle, or ID.
	 * @return bool
	 */
	public function isInGroup($group)
	{
		if (craft()->hasPackage(CraftPackage::Users))
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
		return $this->firstName . ($this->firstName && $this->lastName ? ' ' : '') . $this->lastName;
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
		if ($this->firstName)
		{
			return $this->firstName;
		}
		else
		{
			return $this->username;
		}
	}

	/**
	 * Returns the element's status.
	 *
	 * @return string|null
	 */
	public function getStatus()
	{
		return $this->getAttribute('status');
	}

	/**
	 * Returns the URL to the user's photo.
	 *
	 * @param int $size
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
	 * Returns the URL to the thumbnail for this user for a given size.
	 *
	 * @param int $size
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
	 * @return bool
	 */
	public function can($permission)
	{
		if (craft()->hasPackage(CraftPackage::Users))
		{
			if ($this->admin)
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
	 * Returns the element's CP edit URL.
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		if (craft()->hasPackage(CraftPackage::Users))
		{
			return UrlHelper::getCpUrl('users/'.$this->id);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Populates a new user instance with a given set of attributes.
	 *
	 * @static
	 * @param mixed $attributes
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
	 * @param null $attributes
	 * @param bool $clearErrors
	 * @return bool|void
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
}
