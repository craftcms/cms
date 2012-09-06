<?php
namespace Blocks;

/**
 * Validates the required Site attributes for the installer.
 */
class SiteSettingsModel extends BaseModel
{
	protected function defineAttributes()
	{
		return array(
			'siteName' => AttributeType::Name,
			'siteUrl'  => array(AttributeType::Url, 'required' => true)
		);
	}
}
