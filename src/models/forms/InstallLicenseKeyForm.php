<?php
namespace Blocks;

/**
 * Validates the required Site attributes for the installer.
 */
class InstallLicenseKeyForm extends BaseForm
{
	protected $attributes = array(
		'licensekey' => PropertyType::LicenseKey
	);
}
