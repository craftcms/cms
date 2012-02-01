<?php
namespace Blocks;

/**
 *
 */
class LicenseKey extends BaseModel
{
	protected $tableName = 'licensekeys';

	protected $attributes = array(
		'key' => array('type' => AttributeType::Char, 'length' => 36, 'required' => true, 'unique' => true)
	);
}
