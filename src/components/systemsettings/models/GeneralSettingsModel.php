<?php
namespace Blocks;

/**
 * Validates the required User attributes for the installer.
 */
class GeneralSettingsModel extends BaseModel
{
	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'siteName'   => array(AttributeType::Name, 'required' => true),
			'siteUrl'    => array(AttributeType::Url, 'required' => true),
			/* HIDE */
			'licenseKey' => AttributeType::LicenseKey,
			/* end HIDE */
		);
	}
}
