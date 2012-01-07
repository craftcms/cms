<?php

class UserGroupPermissions extends BlocksDataType
{
	static $belongsTo = array(
		'group' => 'UserGroups'
	);

	static $attributes = array(
		'name' => array('type' => AttributeType::String, 'required' => true),
		'value' => array('type' => AttributeType::Integer, 'required' => true)
	);
}
