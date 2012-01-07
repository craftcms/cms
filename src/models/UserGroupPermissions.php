<?php

class UserGroupPermissions extends BlocksModel
{
	protected static $belongsTo = array(
		'group' => 'UserGroups'
	);

	protected static $attributes = array(
		'name' => array('type' => AttributeType::String, 'required' => true),
		'value' => array('type' => AttributeType::Integer, 'required' => true)
	);
}
