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
			'firstName'                    => AttributeType::String,
			'lastName'                     => AttributeType::String,
			'email'                        => AttributeType::Email,
			'password'                     => AttributeType::String,
			'encType'                      => AttributeType::String,
			'language'                     => AttributeType::Language,
			'emailFormat'                  => array(AttributeType::String, 'default' => 'text'),
			'admin'                        => AttributeType::Bool,
			'status'                       => AttributeType::Enum,
			//'authSessionToken'           => AttributeType::String,
			'lastLoginDate'                => AttributeType::DateTime,
			//'lastLoginAttemptIPAddress'  => AttributeType::String,
			//'invalidLoginWindowStart'    => AttributeType::DateTime,
			'invalidLoginCount'            => AttributeType::Number,
			'lastInvalidLoginDate'         => AttributeType::DateTime,
			'lockoutDate'                  => AttributeType::DateTime,
			//'verificationCode'           => AttributeType::String,
			//'verificationCodeIssuedDate' => AttributeType::DateTime,
			'passwordResetRequired'        => AttributeType::Bool,
			'lastPasswordChangeDate'     => AttributeType::DateTime,
			//'archivedUsername'           => AttributeType::String,
			//'archivedEmail'              => AttributeType::Email,

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
			return blx()->users->getAllBlocks();
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
			return blx()->users->getProfileRecordByUserId($this->id);
		}
	}

	/**
	 * Returns the user's groups.
	 *
	 * @return array|null
	 */
	public function getGroups()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			return blx()->userGroups->getGroupsByUserId($this->id);
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
	 * Returns whether this is the current logged-in user.
	 *
	 * @return bool
	 */
	function isCurrent()
	{
		if ($this->id)
		{
			$currentUser = blx()->account->getCurrentUser();

			if ($currentUser)
			{
				return ($this->id == $currentUser->id);
			}
		}

		return false;
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
			$cooldownEnd->add(new DateInterval(blx()->config->cooldownDuration));

			return $cooldownEnd;
		}
	}

	/**
	 * Returns the remaining cooldown time for this user, if they've entered their password incorrectly too many times.
	 *
	 * @return int|null The number of seconds left until cooldown is over.
	 */
	public function getRemainingCooldownTime()
	{
		if ($this->status == UserStatus::Locked)
		{
			$currentTime = new DateTime();
			$cooldownEnd = $this->getCooldownEndTime();

			if ($currentTime < $cooldownEnd)
			{
				return $cooldownEnd->getTimestamp() - $currentTime->getTimestamp();
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
			$cooldownDuration = blx()->config->cooldownDuration;

			if ($cooldownDuration)
			{
				if (!$user->getRemainingCooldownTime())
				{
					blx()->account->activateUser($user);
				}
			}
		}

		return $user;
	}
}
