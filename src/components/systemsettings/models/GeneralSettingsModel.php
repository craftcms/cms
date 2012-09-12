<?php
namespace Blocks;

/**
 * Validates the required User attributes for the installer.
 */
class GeneralSettingsModel extends BaseModel
{
	public function defineAttributes()
	{
		return array(
			'siteName'   => array(AttributeType::Name, 'required' => true),
			'siteUrl'    => array(AttributeType::Url, 'required' => true),
			/* BLOCKSPRO ONLY */
			'licenseKey' => AttributeType::LicenseKey,
			/* end BLOCKSPRO ONLY */
		);
	}
}
