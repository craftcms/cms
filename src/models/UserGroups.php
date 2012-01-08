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

	protected static $hasAndBelongsToMany = array(
		'users' => 'Users.groups'
	);

	protected static $hasMany = array(
		'permissions' => 'UserGroupPermissions.group'
	);

	protected static $attributes = array(
		'name'        => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true),
		'description' => array('type' => AttributeType::Text)
	);
}
