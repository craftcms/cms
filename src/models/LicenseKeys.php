<?php

class LicenseKeys extends BaseModel
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

	protected $attributes = array(
		'key' => array('type' => AttributeTypes::String, 'maxLength' => 36, 'required' => true)
	);
}
