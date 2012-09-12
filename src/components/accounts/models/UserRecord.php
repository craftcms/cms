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
			'username'                         => array(AttributeType::String, 'maxLength' => 100, 'required' => true, 'unique' => true),
			'firstName'                        => array(AttributeType::String, 'maxLength' => 100),
			'lastName'                         => array(AttributeType::String, 'maxLength' => 100),
			'email'                            => array(AttributeType::Email, 'required' => true, 'unique' => true),
			'password'                         => array(AttributeType::String, 'maxLength' => 255, 'column' => ColumnType::Char),
			'encType'                          => array(AttributeType::String, 'maxLength' => 10, 'column' => ColumnType::Char),
			'authSessionToken'                 => array(AttributeType::String, 'maxLength' => 100, 'column' => ColumnType::Char),
			'admin'                            => AttributeType::Bool,
			'passwordResetRequired'            => AttributeType::Bool,
			'status'                           => array(AttributeType::Enum, 'values' => array('locked', 'suspended', 'pending', 'active', 'archived'), 'default' => 'pending'),
			/* BLOCKSPRO ONLY */
			'language'                         => array(AttributeType::Language, 'required' => true, 'default' => Blocks::getLanguage()),
			/* end BLOCKSPRO ONLY */
			'emailFormat'                      => array(AttributeType::Enum, 'values' => array('text', 'html'), 'default' => 'text', 'required' => true),
			'lastLoginDate'                    => AttributeType::DateTime,
			'lastLoginFailedDate'              => AttributeType::DateTime,
			'lastLoginAttemptIPAddress'        => array(AttributeType::String, 'required' => true, 'maxLength' => 45),
			'lastPasswordChangeDate'           => AttributeType::DateTime,
			'lastLockoutDate'                  => AttributeType::DateTime,
			'failedPasswordAttemptCount'       => array(AttributeType::Number, 'column' => ColumnType::TinyInt, 'unsigned' => true),
			'failedPasswordAttemptWindowStart' => AttributeType::DateTime,
			'cooldownStart'                    => AttributeType::DateTime,
			'verificationCode'                 => array(AttributeType::String, 'maxLength' => 36, 'column' => ColumnType::Char),
			'verificationCodeIssuedDate'       => AttributeType::DateTime,
			'verificationCodeExpiryDate'       => AttributeType::DateTime,
			'archivedUsername'                 => array(AttributeType::String, 'maxLength' => 100),
			'archivedEmail'                    => AttributeType::Email,
		);
	}

	public function defineRelations()
	{
		return array(
			/* BLOCKSPRO ONLY */
			'content' => array(static::HAS_ONE, 'UserContentRecord', 'userId'),
			/* end BLOCKSPRO ONLY */
			'widgets' => array(static::HAS_MANY, 'Widget', 'userId'),
		);
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
	 * Returns whether this is the current logged-in user.
	 *
	 * @return bool
	 */
	public function isCurrent()
	{
		return (!$this->isNewRecord() && $this->id == blx()->accounts->getCurrentUser()->id);
	}

	/**
	 * Returns the remaining cooldown time for this user, if they've entered their password incorrectly too many times.
	 *
	 * @return mixed
	 */
	public function getRemainingCooldownTime()
	{
		return blx()->accounts->getRemainingCooldownTime($this);
	}
}
