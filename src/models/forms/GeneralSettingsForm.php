<?php
namespace Blocks;

/**
 * Validates the required User attributes for the installer.
 */
class GeneralSettingsForm extends BaseForm
{
	protected $attributes = array(
		'siteName'   => array('type' => PropertyType::Name, 'required' => true),
		'siteUrl'    => array('type' => PropertyType::Url, 'required' => true),
		/* BLOCKSPRO ONLY */
		'licenseKey' => PropertyType::LicenseKey,
		/* end BLOCKSPRO ONLY */
	);
}
