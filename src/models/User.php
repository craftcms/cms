<?php
namespace Blocks;

/**
 *
 */
class User extends BaseModel
{
	protected $tableName = 'users';

	protected $attributes = array(
		'username'                              => array('type' => AttributeType::Varchar, 'maxLength' => 100, 'required' => true, 'unique' => true),
		'first_name'                            => array('type' => AttributeType::Varchar, 'maxLength' => 100),
		'last_name'                             => array('type' => AttributeType::Varchar, 'maxLength' => 100),
		'email'                                 => array('type' => AttributeType::Email, 'required' => true, 'unique' => true),
		'password'                              => array('type' => AttributeType::Char),
		'enc_type'                              => array('type' => AttributeType::Char, 'maxLength' => 10),
		'auth_session_token'                    => array('type' => AttributeType::Char, 'maxLength' => 100),
		'admin'                                 => AttributeType::Boolean,
		'password_reset_required'               => AttributeType::Boolean,
		'status'                                => array('type' => AttributeType::Enum, 'values' => array('locked', 'suspended', 'pending', 'active', 'archived'), 'default' => 'pending'),
		'preferred_language'                    => AttributeType::Language,
		'email_format'                          => array('type' => AttributeType::Enum, 'values' => array('text', 'html'), 'default' => 'text', 'required' => true),
		'last_login_date'                       => AttributeType::Int,
		'last_login_failed_date'                => AttributeType::Int,
		'last_password_change_date'             => AttributeType::Int,
		'last_lockout_date'                     => AttributeType::Int,
		'failed_password_attempt_count'         => array('type' => AttributeType::TinyInt, 'unsigned' => true),
		'failed_password_attempt_window_start'  => AttributeType::Int,
		'cooldown_start'                        => AttributeType::Int,
		'verification_code'                     => array('type' => AttributeType::Char, 'maxLength' => 36),
		'verification_code_issued_date'         => array('type' => AttributeType::Int),
		'verification_code_expiry_date'         => array('type' => AttributeType::Int),
		'archived_username'                     => array('type' => AttributeType::Varchar, 'maxLength' => 100),
		'archived_email'                        => array('type' => AttributeType::Email),
	);

	protected $hasMany = array(
		//'members'   => array('model' => 'UserGroupMembers', 'foreignKey' => 'user'),
		//'groups'    => array('model' => 'UserGroup', 'through' => 'UserGroupMember', 'foreignKey' => array('user' => 'group')),
		'widgets'   => array('model' => 'Widget', 'foreignKey' => 'user'),
	);

	/**
	 * String representation of a user
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
		return $this->first_name . ($this->first_name && $this->last_name ? ' ' : '') . $this->last_name;
	}

	/**
	 * Returns the user's first name and last initial
	 * @return string
	 */
	public function getFirstNameLastInitial()
	{
		$name = $this->first_name;
		if ($this->last_name)
			$name .= ' '.substr($this->last_name, 0, 1);
		return $name;
	}

	/**
	 * Returns whether this is the current logged-in user
	 * @return bool
	 */
	public function getIsCurrent()
	{
		return (!$this->getIsNewRecord() && $this->id == blx()->users->getCurrentUser()->id);
	}

	/**
	 * @return mixed
	 */
	public function getRemainingCooldownTime()
	{
		return blx()->users->getRemainingCooldownTime($this);
	}
}
