<?php

class Users extends BlocksModel
{
	private static $hasContent = true;
	private static $hasCustomBlocks = true;

	private static $hasAndBelongsToMany = array(
		'groups' => 'UserGroups.users'
	);

	private static $attributes = array(
		'username'    => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true),
		'first_name'  => array('type' => AttributeType::String, 'maxSize' => 100, 'required' => true),
		'last_name'   => array('type' => AttributeType::String, 'maxSize' => 100),
		'email'       => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true),
		'password'    => array('type' => AttributeType::String, 'maxSize' => 128, 'required' => true),
		'salt'        => array('type' => AttributeType::String, 'maxSize' => 128, 'required' => true)
	);
}
