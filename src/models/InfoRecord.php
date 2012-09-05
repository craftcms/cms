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

	protected function getProperties()
	{
		return array(
			'version'     => PropertyType::Version,
			'build'       => PropertyType::Build,
			'releaseDate' => array(PropertyType::Int, 'required' => true),
			'siteName'    => PropertyType::Name,
			'siteUrl'     => array(PropertyType::Url, 'required' => true),
			'language'    => PropertyType::Language,
			'licenseKey'  => PropertyType::LicenseKey,
			'on'          => PropertyType::Boolean,
		);
	}
}
