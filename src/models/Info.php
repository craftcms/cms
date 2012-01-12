<?php

/**
 *
 */
class Info extends BaseModel
{
	/**
	 * Returns an instance of the specified model
	 * @static
	 * @param string $class
	 * @return object The model instance
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	protected $attributes = array(
		'edition' => array('type' => AttributeType::Enum, 'values' => 'Pro,Standard,Personal', 'required' => true),
		'version' => array('type' => AttributeType::String, 'maxSize' => 15, 'required' => true),
		'build'   => array('type' => AttributeType::Integer, 'required' => true)
	);
}
