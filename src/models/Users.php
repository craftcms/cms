<?php

/**
 *
 */
class Users extends BaseModel
{
	protected $attributes = array(
		'username'                              => array('type' => AttributeType::String,  'required'  => true),
		'first_name'                            => array('type' => AttributeType::String,  'maxLength' => 100, 'required' => true),
		'last_name'                             => array('type' => AttributeType::String,  'maxLength' => 100),
		'email'                                 => array('type' => AttributeType::String,  'required'  => true),
		'password'                              => array('type' => AttributeType::String,  'maxLength' => 128, 'required' => true),
		'enc_type'                              => array('type' => AttributeType::String,  'maxLength' => 32, 'required' => true),
		'auth_token'                            => array('type' => AttributeType::String,  'maxLength' => 32),
		'password_reset_required'               => array('type' => AttributeType::Boolean, 'unsigned'  => true),
		'last_login_date'                       => array('type' => AttributeType::Integer, 'maxLength' => 11),
		'last_password_change_date'             => array('type' => AttributeType::Integer, 'maxLength' => 11),
		'last_lockout_date'                     => array('type' => AttributeType::Integer, 'maxLength' => 11),
		'failed_password_attempt_count'         => array('type' => AttributeType::Integer, 'maxLength' => 11, 'unsigned' => true),
		'failed_password_attempt_window_start'  => array('type' => AttributeType::Integer, 'maxLength' => 11)
	);

	protected $hasContent = array(
		'content' => array('through' => 'UserContent', 'foreignKey' => 'user')
	);

	protected $hasMany = array(
		'members' => array('model' => 'UserGroupMembers', 'foreignKey' => 'user'),
		'groups'  => array('model' => 'UserGroups', 'through' => 'UserGroupMembers', 'foreignKey' => array('user'=>'group')),
		'widgets' => array('model' => 'UserWidgets', 'foreignKey' => 'user')
	);

	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
