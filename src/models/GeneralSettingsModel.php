<?php
namespace Blocks;

/**
 * Validates the required User attributes for the installer.
 */
class GeneralSettingsModel extends BaseModel
{
	protected function defineAttributes()
	{
		return array(
			'siteName'   => AttributeType::Name,
			'siteUrl'    => array(AttributeType::Url, 'required' => true),
			/* BLOCKSPRO ONLY */
			'licenseKey' => AttributeType::LicenseKey,
			/* end BLOCKSPRO ONLY */
		);
	}
}
