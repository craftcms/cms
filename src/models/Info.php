<?php
namespace Blocks;

/**
 *
 */
class Info extends BaseModel
{
	public function getTableName()
	{
		return 'info';
	}

	protected function getProperties()
	{
		return array(
			'version'       => PropertyType::Version,
			'build'         => PropertyType::Build,
			'release_date'  => array(PropertyType::Int, 'required' => true),
			'site_name'     => PropertyType::Name,
			'site_url'      => array(PropertyType::Url, 'required' => true),
			'language'      => PropertyType::Language,
			'license_key'   => PropertyType::LicenseKey,
			'on'            => PropertyType::Boolean,
		);
	}
}
