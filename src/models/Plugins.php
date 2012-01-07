<?php

class Plugins extends BlocksModel
{
	protected static $hasSettings = true;

	protected static $attributes = array(
		'name'    => array('type' => AttributeType::String, 'maxSize' => 50),
		'version' => array('type' => AttributeType::String, 'maxSize' => 15),
		'enabled' => array('type' => AttributeType::Boolean, 'default' => true, 'required' => true)
	);
}
