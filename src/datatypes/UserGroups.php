<?php

class UserGroups extends BlocksDataType
{
	private static $hasAndBelongsToMany = array(
		'users' => 'Users.groups'
	);

	private static $hasMany = array(
		'permissions' => 'UserGroupPermissions.group'
	);

	private static $attributes = array(
		'name'        => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true),
		'description' => array('type' => AttributeType::Text)
	);
}
