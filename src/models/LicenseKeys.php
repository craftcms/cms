<?php

/**
 *
 */
class LicenseKeys extends BaseModel
{
	/**
	 * Returns an instance of the specified model
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $class
	 *
	 * @return object The model instance
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	protected $attributes = array(
		'key' => array('type' => AttributeType::String, 'maxLength' => 36, 'required' => true)
	);
}
