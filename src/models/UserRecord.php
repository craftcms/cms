<?php
namespace Blocks;

/**
 *
 */
class UserRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'users';
	}

	public function defineAttributes()
	{
		return array(
			'username'                         => array(AttributeType::Varchar, 'maxLength' => 100, 'required' => true, 'unique' => true),
			'firstName'                        => array(AttributeType::Varchar, 'maxLength' => 100),
			'lastName'                         => array(AttributeType::Varchar, 'maxLength' => 100),
			'email'                            => array(AttributeType::Email, 'required' => true, 'unique' => true),
			'password'                         => AttributeType::Char,
			'encType'                          => array(AttributeType::Char, 'maxLength' => 10),
			'authSessionToken'                 => array(AttributeType::Char, 'maxLength' => 100),
			'admin'                            => AttributeType::Boolean,
			'passwordResetRequired'            => AttributeType::Boolean,
			'status'                           => array(AttributeType::Enum, 'values' => array('locked', 'suspended', 'pending', 'active', 'archived'), 'default' => 'pending'),
			/* BLOCKSPRO ONLY */
			'language'                         => array(AttributeType::Language, 'default' => Blocks::getLanguage()),
			/* end BLOCKSPRO ONLY */
			'emailFormat'                      => array(AttributeType::Enum, 'values' => array('text', 'html'), 'default' => 'text', 'required' => true),
			'lastLoginDate'                    => AttributeType::UnixTimeStamp,
			'lastLoginFailedDate'              => AttributeType::UnixTimeStamp,
			'lastPasswordChangeDate'           => AttributeType::UnixTimeStamp,
			'lastLockoutDate'                  => AttributeType::UnixTimeStamp,
			'failedPasswordAttemptCount'       => array(AttributeType::TinyInt, 'unsigned' => true),
			'failedPasswordAttemptWindowStart' => AttributeType::UnixTimeStamp,
			'cooldownStart'                    => AttributeType::UnixTimeStamp,
			'verificationCode'                 => array(AttributeType::Char, 'maxLength' => 36),
			'verificationCodeIssuedDate'       => AttributeType::UnixTimeStamp,
			'verificationCodeExpiryDate'       => AttributeType::UnixTimeStamp,
			'archivedUsername'                 => array(AttributeType::Varchar, 'maxLength' => 100),
			'archivedEmail'                    => AttributeType::Email,
		);
	}

	public function defineRelations()
	{
		return array(
			/* BLOCKSPRO ONLY */
			'blocks'  => array(static::HAS_MANY, 'UserBlockRecord', 'userId'),
			'content' => array(static::HAS_ONE, 'UserContentRecord', 'userId'),
			/* end BLOCKSPRO ONLY */
			'widgets' => array(static::HAS_MANY, 'Widget', 'userId'),
		);
	}

	/**
	 * String representation of a user
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->getFullName();
	}

	/**
	 * Returns the user's full name (first+last name), if it's available.
	 *
	 * @return string
	 */
	public function getFullName()
	{
		return $this->firstName . ($this->firstName && $this->lastName ? ' ' : '') . $this->lastName;
	}

	/**
	 * Returns whether this is the current logged-in user
	 *
	 * @return bool
	 */
	public function getIsCurrent()
	{
		return (!$this->getIsNewRecord() && $this->id == blx()->accounts->getCurrentUser()->id);
	}

	/**
	 * @return mixed
	 */
	public function getRemainingCooldownTime()
	{
		return blx()->accounts->getRemainingCooldownTime($this);
	}
}
