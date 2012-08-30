<?php
namespace Blocks;

/**
 *
 */
class User extends BaseModel
{
	public function getTableName()
	{
		return 'users';
	}

	protected function getProperties()
	{
		return array(
			'username'                             => array(PropertyType::Varchar, 'maxLength' => 100, 'required' => true, 'unique' => true),
			'first_name'                           => array(PropertyType::Varchar, 'maxLength' => 100),
			'last_name'                            => array(PropertyType::Varchar, 'maxLength' => 100),
			'email'                                => array(PropertyType::Email, 'required' => true, 'unique' => true),
			'password'                             => PropertyType::Char,
			'enc_type'                             => array(PropertyType::Char, 'maxLength' => 10),
			'auth_session_token'                   => array(PropertyType::Char, 'maxLength' => 100),
			'admin'                                => PropertyType::Boolean,
			'password_reset_required'              => PropertyType::Boolean,
			'status'                               => array(PropertyType::Enum, 'values' => array('locked', 'suspended', 'pending', 'active', 'archived'), 'default' => 'pending'),
			'language'                             => array(PropertyType::Language, 'default' => Blocks::getLanguage()),
			'email_format'                         => array(PropertyType::Enum, 'values' => array('text', 'html'), 'default' => 'text', 'required' => true),
			'last_login_date'                      => PropertyType::Int,
			'last_login_failed_date'               => PropertyType::Int,
			'last_password_change_date'            => PropertyType::Int,
			'last_lockout_date'                    => PropertyType::Int,
			'failed_password_attempt_count'        => array(PropertyType::TinyInt, 'unsigned' => true),
			'failed_password_attempt_window_start' => PropertyType::Int,
			'cooldown_start'                       => PropertyType::Int,
			'verification_code'                    => array(PropertyType::Char, 'maxLength' => 36),
			'verification_code_issued_date'        => PropertyType::Int,
			'verification_code_expiry_date'        => PropertyType::Int,
			'archived_username'                    => array(PropertyType::Varchar, 'maxLength' => 100),
			'archived_email'                       => PropertyType::Email,
		);
	}

	protected function getRelations()
	{
		return array(
			'blocks'  => array(static::HAS_MANY, 'UserBlock', 'user_id'),
			'content' => array(static::HAS_ONE, 'UserContent', 'user_id'),
			'widgets' => array(static::HAS_MANY, 'Widget', 'user_id'),
		);
	}

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
	 * Returns whether this is the current logged-in user
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
