<?php

class Info extends BlocksModel
{
	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	protected static $attributes = array(
		'edition' => array('type' => AttributeType::Enum, 'values' => 'Pro,Standard,Personal', 'required' => true),
		'version' => array('type' => AttributeType::String, 'maxSize' => 15, 'required' => true),
		'build'   => array('type' => AttributeType::Integer, 'required' => true)
	);
}
