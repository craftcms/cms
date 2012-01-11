<?php

class Plugins extends BaseModel
{
	/**
	 * Returns an instance of the specified model
	 *
	 * @param string $class
	 *
	 * @return object The model instance
	 * @static
	*/
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	protected $hasMany = array(
		'settings' => array('model' => 'PluginSettings', 'foreignKey' => 'plugin')
	);

	protected $attributes = array(
		'name'    => array('type' => AttributeType::String, 'maxSize' => 50),
		'version' => array('type' => AttributeType::String, 'maxSize' => 15),
		'enabled' => array('type' => AttributeType::Boolean, 'default' => true, 'required' => true)
	);
}
