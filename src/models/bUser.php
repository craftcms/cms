<?php

/**
 *
 */
class bUser extends bBaseModel
{
	protected $tableName = 'users';

	protected $attributes = array(
		'username'                              => array('type' => bAttributeType::String,  'required'  => true),
		'first_name'                            => array('type' => bAttributeType::String,  'maxLength' => 100, 'required' => true),
		'last_name'                             => array('type' => bAttributeType::String,  'maxLength' => 100),
		'email'                                 => array('type' => bAttributeType::String,  'required'  => true),
		'password'                              => array('type' => bAttributeType::String,  'maxLength' => 128, 'required' => true),
		'enc_type'                              => array('type' => bAttributeType::String,  'maxLength' => 32, 'required' => true),
		'auth_token'                            => array('type' => bAttributeType::String,  'maxLength' => 32),
		'password_reset_required'               => array('type' => bAttributeType::Boolean, 'unsigned'  => true),
		'last_login_date'                       => array('type' => bAttributeType::Integer, 'maxLength' => 11),
		'last_password_change_date'             => array('type' => bAttributeType::Integer, 'maxLength' => 11),
		'last_lockout_date'                     => array('type' => bAttributeType::Integer, 'maxLength' => 11),
		'failed_password_attempt_count'         => array('type' => bAttributeType::Integer, 'maxLength' => 11, 'unsigned' => true),
		'failed_password_attempt_window_start'  => array('type' => bAttributeType::Integer, 'maxLength' => 11)
	);

	protected $hasContent = array(
		'content' => array('through' => 'bUserContent', 'foreignKey' => 'user')
	);

	protected $hasMany = array(
		'members' => array('model' => 'bUserGroupMembers', 'foreignKey' => 'user'),
		'groups'  => array('model' => 'bUserGroup', 'through' => 'bUserGroupMembers', 'foreignKey' => array('user'=>'group')),
		'widgets' => array('model' => 'bUserWidget', 'foreignKey' => 'user')
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
