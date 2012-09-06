<?php
namespace Blocks;

/**
 * Validates the required Site attributes for the installer.
 */
class LicenseKeyModel extends BaseModel
{
	public function defineAttributes()
	{
		return array(
			'licensekey' => AttributeType::LicenseKey
		);
	}
}
