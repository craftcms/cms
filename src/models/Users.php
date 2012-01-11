<?php

class Users extends BaseModel
{
	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	protected $hasBlocks = array(
		'blocks' => array('through' => 'UserBlocks', 'foreignKey' => 'user')
	);

	protected $hasContent = array(
		'content' => array('through' => 'UserContent', 'foreignKey' => 'user')
	);

	protected $hasMany = array(
		'members' => array('model' => 'UserGroupMembers', 'foreignKey' => 'user'),
		'groups' => array('model' => 'UserGroups', 'through' => 'UserGroupMembers', 'foreignKey' => array('user'=>'group')))
	);

	protected $attributes = array(
		'username'    => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true),
		'first_name'  => array('type' => AttributeType::String, 'maxSize' => 100, 'required' => true),
		'last_name'   => array('type' => AttributeType::String, 'maxSize' => 100),
		'email'       => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true),
		'password'    => array('type' => AttributeType::String, 'maxSize' => 128, 'required' => true),
		'salt'        => array('type' => AttributeType::String, 'maxSize' => 128, 'required' => true)
	);
}
