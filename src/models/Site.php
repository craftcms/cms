<?php
namespace Blocks;

/**
 *
 */
class Site extends Model
{
	protected $tableName = 'sites';
	protected $hasBlocks = true;
	protected $hasContent = true;
	protected $hasSettings = true;

	protected $attributes = array(
		'language' => AttributeType::Language,
		'name'     => AttributeType::Name,
		'handle'   => AttributeType::Handle,
		'url'      => array('type' => AttributeType::Url, 'required' => true),
		'primary'  => array('type' => AttributeType::Boolean, 'required' => false, 'default' => null, 'unique' => true)
	);

	protected $hasMany = array(
		'assetFolders' => array('model' => 'AssetFolder', 'foreignKey' => 'site'),
		'routes'       => array('model' => 'Route', 'foreignKey' => 'site'),
		'sections'     => array('model' => 'Section', 'foreignKey' => 'site')
	);
}
