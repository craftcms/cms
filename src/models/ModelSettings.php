<?php

class ModelSettings extends BlocksModel
{
	protected static $attributes = array(
		'key'   => array('type' => AttributeType::String, 'maxSize' => 100, 'required' => true),
		'value' => array('type' => AttirbuteType::Text)
	);
}
