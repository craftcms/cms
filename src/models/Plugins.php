<?php

class Plugins extends BlocksModel
{
	private static $hasSettings = true;

	private static $attributes = array(
		'name'    => array('type' => AttributeType::String, 'maxSize' => 50),
		'version' => array('type' => AttributeType::String, 'maxSize' => 15),
		'enabled' => array('type' => AttributeType::Boolean, 'default' => true, 'required' => true)
	);
}
