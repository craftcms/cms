<?php

class Routes extends BlocksModel
{
	protected static $belongsTo = array(
		'site' => 'Sites'
	);

	protected static $attributes = array(
		'route'      => array('type' => AttributeType::String, 'maxSize' => 500, 'required' => true),
		'template'   => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true),
		'sort_order' => array('type' => AttributeType::Integer, 'required' => true)
	);
}
