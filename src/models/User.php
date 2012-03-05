<?php
namespace Blocks;

/**
 *
 */
class User extends BaseModel
{
	protected $tableName = 'users';
	protected $hasContent = true;

	protected $attributes = array(
		'username'                              => array('type' => AttributeType::Varchar, 'maxLength' => 100, 'required' => true, 'unique' => true),
		'first_name'                            => array('type' => AttributeType::Varchar, 'maxLength' => 100, 'required' => true),
		'last_name'                             => array('type' => AttributeType::Varchar, 'maxLength' => 100),
		'email'                                 => array('type' => AttributeType::Email, 'required' => true, 'unique' => true),
		'password'                              => array('type' => AttributeType::Char),
		'enc_type'                              => array('type' => AttributeType::Char, 'maxLength' => 10),
		'auth_session_token'                    => array('type' => AttributeType::Char, 'maxLength' => 100),
		'admin'                                 => AttributeType::Boolean,
		'password_reset_required'               => AttributeType::Boolean,
		'status'                                => array('type' => AttributeType::Enum, 'values' => array('locked', 'suspended', 'pending', 'active', 'archived'), 'default' => 'pending'),
		'html_email'                            => array('type' => AttributeType::Boolean, 'default' => true),
		'last_login_date'                       => AttributeType::Int,
		'last_login_failed_date'                => AttributeType::Int,
		'last_password_change_date'             => AttributeType::Int,
		'last_lockout_date'                     => AttributeType::Int,
		'failed_password_attempt_count'         => array('type' => AttributeType::TinyInt, 'unsigned' => true),
		'failed_password_attempt_window_start'  => AttributeType::Int,
		'cooldown_start'                        => AttributeType::Int,
		'activationcode'                        => array('type' => AttributeType::Char, 'maxLength' => 36),
		'activationcode_issued_date'            => array('type' => AttributeType::Int),
		'activationcode_expire_date'            => array('type' => AttributeType::Int),
		'archived_username'                     => array('type' => AttributeType::Varchar, 'maxLength' => 100),
		'archived_email'                        => array('type' => AttributeType::Email),
	);

	protected $hasMany = array(
		'members'   => array('model' => 'UserGroupMembers', 'foreignKey' => 'user'),
		'groups'    => array('model' => 'UserGroup', 'through' => 'UserGroupMembers', 'foreignKey' => array('user' => 'group')),
		'widgets'   => array('model' => 'UserWidget', 'foreignKey' => 'user'),
	);

	/**
	 * @return string
	 */
	public function getFullName()
	{
		$fullName = $this->first_name;

		if ($this->last_name)
		 	$fullName .= ' '.$this->last_name;

		return $fullName;
	}

	/**
	 * @return mixed
	 */
	public function getRemainingCooldownTime()
	{
		return Blocks::app()->users->getRemainingCooldownTime($this);
	}
}
