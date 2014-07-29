<?php
namespace Craft;

/**
 * Validates the required Site attributes for the installer.
 *
 * @package craft.app.models
 */
class SiteSettingsModel extends BaseModel
{
	/**
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
