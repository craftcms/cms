<?php
namespace Craft;

/**
 * Validates the required Site attributes for the installer.
 */
class LicenseKeyModel extends BaseModel
{
	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'licensekey' => AttributeType::LicenseKey
		);
	}
}
