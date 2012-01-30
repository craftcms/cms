<?php

/**
 *
 */
class bUser extends bBaseModel
{
	protected $tableName = 'users';

	protected $attributes = array(
		'username'                              => array('type' => bAttributeType::Varchar, 'maxLength' => 100, 'required'  => true, 'unique' => true),
		'first_name'                            => array('type' => bAttributeType::Varchar, 'maxLength' => 100, 'required' => true),
		'last_name'                             => array('type' => bAttributeType::Varchar, 'maxLength' => 100),
		'email'                                 => array('type' => bAttributeType::Varchar, 'required'  => true, 'unique' => true),
		'password'                              => array('type' => bAttributeType::Char, 'required' => true),
		'enc_type'                              => array('type' => bAttributeType::Char, 'maxLength' => 10, 'required' => true),
		'auth_token'                            => array('type' => bAttributeType::Char, 'maxLength' => 32),
		'admin'                                 => bAttributeType::Boolean,
		'html_email'                            => bAttributeType::Boolean,
		'password_reset_required'               => bAttributeType::Boolean,
		'last_login_date'                       => bAttributeType::Int,
		'last_password_change_date'             => bAttributeType::Int,
		'last_lockout_date'                     => bAttributeType::Int,
		'failed_password_attempt_count'         => array('type' => bAttributeType::TinyInt, 'unsigned' => true),
		'failed_password_attempt_window_start'  => bAttributeType::Int
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
