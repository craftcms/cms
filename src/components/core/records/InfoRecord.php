<?php
namespace Blocks;

/**
 *
 */
class InfoRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'info';
	}

	public function defineAttributes()
	{
		return array(
			'version'     => array(AttributeType::Version, 'required' => true),
			'build'       => array(AttributeType::Build, 'required' => true),
			'releaseDate' => array(AttributeType::DateTime, 'required' => true),
			'siteName'    => array(AttributeType::Name, 'required' => true),
			'siteUrl'     => array(AttributeType::Url, 'required' => true),
			'language'    => array(AttributeType::Language, 'required' => true),
			'licenseKey'  => AttributeType::LicenseKey,
			'on'          => AttributeType::Bool,
		);
	}
}
