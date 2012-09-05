<?php
namespace Blocks;

/**
 * Validates the required Site attributes for the installer.
 */
class SiteSettingsModel extends BaseModel
{
	protected function getProperties()
	{
		return array(
			'siteName' => PropertyType::Name,
			'siteUrl'  => array(PropertyType::Url, 'required' => true)
		);
	}
}
