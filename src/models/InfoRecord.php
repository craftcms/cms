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
			'version'     => AttributeType::Version,
			'build'       => AttributeType::Build,
			'releaseDate' => array(AttributeType::Int, 'required' => true),
			'siteName'    => AttributeType::Name,
			'siteUrl'     => array(AttributeType::Url, 'required' => true),
			'language'    => AttributeType::Language,
			'licenseKey'  => AttributeType::LicenseKey,
			'on'          => AttributeType::Boolean,
		);
	}
}
