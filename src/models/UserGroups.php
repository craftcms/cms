<?php

class UserGroups extends BlocksModel
{
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
