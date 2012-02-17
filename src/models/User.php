<?php
namespace Blocks;

/**
 *
 */
class User extends BaseModel
{
	protected $tableName = 'users';

	protected $attributes = array(
		'username'                              => array('type' => AttributeType::Varchar, 'maxLength' => 100, 'required'  => true, 'unique' => true),
		'first_name'                            => array('type' => AttributeType::Varchar, 'maxLength' => 100, 'required' => true),
		'last_name'                             => array('type' => AttributeType::Varchar, 'maxLength' => 100),
		'email'                                 => array('type' => AttributeType::Email, 'required'  => true, 'unique' => true),
		'password'                              => array('type' => AttributeType::Char, 'required' => true),
		'enc_type'                              => array('type' => AttributeType::Char, 'maxLength' => 10, 'required' => true),
		'auth_session_token'                    => array('type' => AttributeType::Char, 'maxLength' => 100),
		'admin'                                 => AttributeType::Boolean,
		'password_reset_required'               => AttributeType::Boolean,
		'html_email'                            => array('type' => AttributeType::Boolean, 'default' => true),
		'last_login_date'                       => AttributeType::Int,
		'last_password_change_date'             => AttributeType::Int,
		'last_lockout_date'                     => AttributeType::Int,
		'failed_password_attempt_count'         => array('type' => AttributeType::TinyInt, 'unsigned' => true),
		'failed_password_attempt_window_start'  => AttributeType::Int
	);

	protected $hasContent = array(
		'content' => array('through' => 'UserContent', 'foreignKey' => 'user')
	);

	protected $hasMany = array(
		'members'   => array('model' => 'UserGroupMembers', 'foreignKey' => 'user'),
		'groups'    => array('model' => 'UserGroup', 'through' => 'UserGroupMembers', 'foreignKey' => array('user' => 'group')),
		'widgets'   => array('model' => 'UserWidget', 'foreignKey' => 'user'),
		'authCodes' => array('model' => 'AuthCode', 'foreignKey' => 'authCode')
	);
}
