<?php

class UserGroups extends BlocksModel
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

	protected static $hasMany = array(
		'members'     => array('model' => 'UserGroupMembers', 'foreignKey' => 'user'),
		'users'       => array('model' => 'Users', 'through' => 'UserGroupMembers', 'foreignKey' => array('group'=>'user'))),
		'permissions' => array('model' => 'UserGroupPermissions', 'foreignKey' => 'group')
	);

	protected static $attributes = array(
		'name'        => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true),
		'description' => array('type' => AttributeType::Text)
	);
}
