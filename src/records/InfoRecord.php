<?php
namespace Craft;

/**
 *
 */
class InfoRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'info';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'version'     => array(AttributeType::Version, 'required' => true),
			'build'       => array(AttributeType::Build, 'required' => true),
			'packages'    => array(AttributeType::String, 'maxLength' => 200),
			'releaseDate' => array(AttributeType::DateTime, 'required' => true),
			'siteName'    => array(AttributeType::Name, 'required' => true),
			'siteUrl'     => array(AttributeType::Url, 'required' => true),
			'licenseKey'  => array(AttributeType::LicenseKey, 'required' => true),
			'on'          => AttributeType::Bool,
			'maintenance' => AttributeType::Bool,
		);
	}
}
