<?php

/**
 *
 */
class LicenseKeys extends BaseModel
{
	protected $attributes = array(
		'key' => array('type' => AttributeType::String, 'maxLength' => 36, 'required' => true)
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
