<?php
namespace Craft;

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
			'on'       => AttributeType::Bool,
			'siteName' => array(AttributeType::Name, 'required' => true),
			'siteUrl'  => array(AttributeType::Url, 'required' => true, 'label' => 'Site URL'),
		);
	}
}
