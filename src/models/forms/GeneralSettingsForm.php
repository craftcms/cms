<?php
namespace Blocks;

/**
 * Validates the required User attributes for the installer.
 */
class GeneralSettingsForm extends BaseForm
{
	protected function getProperties()
	{
		return array(
			'siteName'   => PropertyType::Name,
			'siteUrl'    => array(PropertyType::Url, 'required' => true),
			/* BLOCKSPRO ONLY */
			'licenseKey' => PropertyType::LicenseKey,
			/* end BLOCKSPRO ONLY */
		);
	}
}
