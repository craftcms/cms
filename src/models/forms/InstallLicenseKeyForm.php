<?php
namespace Blocks;

/**
 * Validates the required Site attributes for the installer.
 */
class InstallLicenseKeyForm extends FormModel
{
	protected $attributes = array(
		'licensekey' => AttributeType::LicenseKey
	);
}
