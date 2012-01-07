<?php

class Info extends BlocksModel
{
	private static $attributes = array(
		'edition' => array('type' => AttributeType::Enum, 'values' => 'Pro,Standard,Personal', 'required' => true),
		'version' => array('type' => AttributeType::String, 'maxSize' => 15, 'required' => true),
		'build'   => array('type' => AttributeType::Integer, 'required' => true)
	);
}
