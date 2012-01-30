<?php
namespace Blocks;

/**
 *
 */
class LicenseKey extends BaseModel
{
	protected $tableName = 'licensekeys';

	protected $attributes = array(
		'key' => array('type' => AttributeType::String, 'maxLength' => 73, 'required' => true, 'unique' => true)
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
