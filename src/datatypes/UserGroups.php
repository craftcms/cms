<?php

class UserGroups extends BlocksDataType
{
	static $hasAndBelongsToMany = array(
		'users' => 'Users.groups'
	);

	static $hasMany = array(
		'permissions' => 'UserGroupPermissions.group'
	);

	static $attributes = array(
		'name'        => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true),
		'description' => array('type' => AttributeType::Text)
	);
}
