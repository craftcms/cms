<?php

/**
 *
 */
class Info extends BaseModel
{
	protected $attributes = array(
		'edition' => array('type' => AttributeType::Enum, 'values' => 'Pro,Standard,Personal', 'required' => true),
		'version' => array('type' => AttributeType::String, 'maxLength' => 15, 'required' => true),
		'build'   => array('type' => AttributeType::Integer, 'required' => true)
	);

	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
