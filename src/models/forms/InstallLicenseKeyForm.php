<?php
namespace Blocks;

/**
 * Validates the required Site attributes for the installer.
 */
class InstallLicenseKeyForm extends BaseForm
{
	protected function getProperties()
	{
		return array(
			'licensekey' => PropertyType::LicenseKey
		);
	}
}
