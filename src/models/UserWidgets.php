<?php

class UserWidgets extends BaseModel
{
	protected $hasMany = array(
		'settings' => array('model' => 'UserWidgetSettings', 'foreignKey' => 'widget')
	);

	protected $belongsTo = array(
		'user' => array('model' => 'Users', 'required' => true)
	);

	protected $attributes = array(
		'class'      => array('type' => AttributeType::String, 'maxSize' => 150, 'required' => true),
		'sort_order' => array('type' => AttributeType::Integer, 'required' => true)
	);
}
