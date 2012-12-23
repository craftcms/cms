<?php
namespace Blocks;

/**
 * User model class
 */
class UserModel extends BaseEntityModel
{
	/**
	 * Use the full name or username as the string representation.
	 *
	 * @return string
	 */
	function __toString()
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
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'id'                           => AttributeType::Number,
			'username'                     => AttributeType::String,
			'photo'                        => AttributeType::String,
			'firstName'                    => AttributeType::String,
			'lastName'                     => AttributeType::String,
			'email'                        => AttributeType::Email,
			'password'                     => AttributeType::String,
			'encType'                      => AttributeType::String,
			'language'                     => AttributeType::Language,
			'emailFormat'                  => array(AttributeType::String, 'default' => 'text'),
			'admin'                        => AttributeType::Bool,
			'status'                       => AttributeType::Enum,
			'lastLoginDate'                => AttributeType::DateTime,
			'invalidLoginCount'            => AttributeType::Number,
			'lastInvalidLoginDate'         => AttributeType::DateTime,
			'lockoutDate'                  => AttributeType::DateTime,
			'passwordResetRequired'        => AttributeType::Bool,
			'lastPasswordChangeDate'       => AttributeType::DateTime,
			'dateCreated'                  => AttributeType::DateTime,
			'verificationRequired'         => AttributeType::Bool,
			'newPassword'                  => AttributeType::String,
		);
	}

	/**
	 * Gets the blocks.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getBlocks()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			return blx()->userProfiles->getAllBlocks();
		}
		else
		{
			return array();
		}
	}

	/**
	 * Gets the content.
	 *
	 * @access protected
	 * @return array|\CModel
	 */
	protected function getContent()
	{
		if ($this->id && Blocks::hasPackage(BlocksPackage::Users))
		{
			return blx()->userProfiles->getProfileRecordByUserId($this->id);
		}
	}

	/**
	 * Returns the user's groups.
	 *
	 * @param string|null $indexBy
	 * @return array|null
	 */
	public function getGroups($indexBy = null)
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			return blx()->userGroups->getGroupsByUserId($this->id, $indexBy);
		}
	}

	/**
	 * Gets the user's full name.
	 *
	 * @return string|null
	 */
	public function getFullName()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			return $this->firstName . ($this->firstName && $this->lastName ? ' ' : '') . $this->lastName;
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
	 * Returns whether this is the current logged-in user.
	 *
	 * @return bool
	 */
	public function isCurrent()
	{
		if ($this->id)
		{
			$currentUser = blx()->user->getUser();

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
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			if ($this->admin)
			{
				return true;
			}
			else if ($this->id)
			{
				return blx()->userPermissions->doesUserHavePermission($this->id, $permission);
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
	 * Returns the time when the user will be over their cooldown period.
	 *
	 * @return DateTime|null
	 */
	public function getCooldownEndTime()
	{
		if ($this->status == UserStatus::Locked)
		{
			$cooldownEnd = clone $this->lockoutDate;
			$cooldownEnd->add(new DateInterval(blx()->config->get('cooldownDuration')));

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
			$cooldownDuration = blx()->config->get('cooldownDuration');

			if ($cooldownDuration)
			{
				if (!$user->getRemainingCooldownTime())
				{
					blx()->users->activateUser($user);
				}
			}
		}

		return $user;
	}
}
