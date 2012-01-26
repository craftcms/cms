<?php

/**
 *
 */
class bLicenseKey extends bBaseModel
{
	protected $tableName = 'licensekeys';

	protected $attributes = array(
		'key' => array('type' => bAttributeType::String, 'maxLength' => 36, 'required' => true)
	);

	protected $indexes = array(
		array('column' => 'key', 'unique' => true),
		array('column' => 'key'),
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
