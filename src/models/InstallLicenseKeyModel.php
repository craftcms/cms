<?php
namespace Blocks;

/**
 * Validates the required Site attributes for the installer.
 */
class InstallLicenseKeyModel extends BaseModel
{
	protected function getProperties()
	{
		return array(
			'licensekey' => PropertyType::LicenseKey
		);
	}
}
