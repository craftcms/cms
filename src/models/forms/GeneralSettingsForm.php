<?php
namespace Blocks;

/**
 * Validates the required User attributes for the installer.
 */
class GeneralSettingsForm extends BaseForm
{
	protected $attributes = array(
		'name'       => array('type' => AttributeType::Name, 'required' => true),
		'url'        => array('type' => AttributeType::Url, 'required' => true),
		'licenseKey' => AttributeType::LicenseKey
	);
}
