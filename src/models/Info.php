<?php
namespace Blocks;

/**
 *
 */
class Info extends BaseModel
{
	protected $tableName = 'info';

	protected $attributes = array(
		'version'       => AttributeType::Version,
		'build'         => AttributeType::Build,
		'release_date'  => array(AttributeType::Int, 'required' => true),
		'site_name'     => array(AttributeType::Name, 'required' => true),
		'site_url'      => array(AttributeType::Url, 'required' => true),
		'language'      => AttributeType::Language,
		'license_key'   => AttributeType::LicenseKey,
		'on'            => AttributeType::Boolean,
	);
}
