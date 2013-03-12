<?php
namespace Craft;

/**
 * Validates the required Site attributes for the installer.
 */
class SiteSettingsModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'siteName' => array(AttributeType::Name, 'required' => true),
			'siteUrl'  => array(AttributeType::Url, 'required' => true)
		);
	}
}
