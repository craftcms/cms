<?php

class UserWidgets extends BaseModel
{
	protected $hasSettings = array(
		'settings' => array('through' => 'UserWidgetSettings', 'foreignKey' => 'widget')
	);

	protected $belongsTo = array(
		'user' => array('model' => 'Users', 'required' => true)
	);

	protected $attributes = array(
		'class'      => array('type' => AttributeType::String, 'maxSize' => 150, 'required' => true),
		'sort_order' => array('type' => AttributeType::Integer, 'required' => true)
	);
}
