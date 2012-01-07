<?php

class Entries extends BlocksDataType
{
	static $hasContent = true;

	static $hasMany = array(
		'children' => 'Entries.parent'
	);

	static $belongsTo = array(
		'parent'  => 'Entries',
		'section' => 'Sections',
		'author'  => 'Users'
	);

	static $attributes = array(
		'slug'        => array('type' => AttributeType::String, 'maxSize' => 250),
		'full_uri'    => array('type' => AttributeType::String, 'maxSize' => 1000),
		'post_date'   => array('type' => AttributeType::Integer),
		'expiry_date' => array('type' => AttributeType::Integer),
		'sort_order'  => array('type' => AttributeType::Integer),
		'enabled'     => array('type' => AttributeType::Boolean, 'required' => true, 'default' => true),
		'archived'    => array('type' => AttributeType::Boolean, 'required' => true, 'default' => false)
	);
}
