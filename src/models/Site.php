<?php
namespace Blocks;

/**
 *
 */
class Site extends BaseModel
{
	protected $tableName = 'sites';

	protected $attributes = array(
		'language'    => AttributeType::Language,
		'name'        => AttributeType::Name,
		'handle'      => AttributeType::Handle,
		'url'         => array('type' => AttributeType::Url, 'required' => true),
		'license_key' => AttributeType::LicenseKey,
		'primary'     => array('type' => AttributeType::Boolean),
		'enabled'     => array('type' => AttributeType::Boolean, 'default' => true)
	);

	protected $hasMany = array(
		'sections' => array('model' => 'Section', 'foreignKey' => 'site')
	);
}
