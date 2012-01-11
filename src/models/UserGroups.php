<?php

class UserGroups extends BaseModel
{
	/**
	 * Returns an instance of the specified model
	 *
	 * @param string $class
	 *
	 * @return object The model instance
	 * @static
	*/
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	protected $hasMany = array(
		'members'     => array('model' => 'UserGroupMembers', 'foreignKey' => 'user'),
		'users'       => array('model' => 'Users', 'through' => 'UserGroupMembers', 'foreignKey' => array('group'=>'user')),
		'permissions' => array('model' => 'UserGroupPermissions', 'foreignKey' => 'group')
	);

	protected $attributes = array(
		'name'        => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true),
		'description' => array('type' => AttributeType::Text)
	);
}
