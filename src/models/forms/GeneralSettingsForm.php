<?php
namespace Blocks;

/**
 * Validates the required User attributes for the installer.
 */
class GeneralSettingsForm extends BaseForm
{
	protected $attributes = array(
		'siteName'   => array('type' => AttributeType::Name, 'required' => true),
		'siteUrl'    => array('type' => AttributeType::Url, 'required' => true),
		/* BLOCKSPRO ONLY */
		'licenseKey' => AttributeType::LicenseKey,
		/* end BLOCKSPRO ONLY */
	);
}
