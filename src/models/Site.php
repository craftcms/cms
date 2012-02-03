<?php
namespace Blocks;

/**
 *
 */
class Site extends BaseModel
{
	protected $tableName = 'sites';

	protected $attributes = array(
		'name'    => AttributeType::Name,
		'handle'  => AttributeType::Handle,
		'url'     => array('type' => AttributeType::Varchar, 'required' => true),
		'primary' => array('type' => AttributeType::Boolean, 'required' => false, 'default' => null, 'unique' => true)
	);

	protected $hasBlocks = array(
		'blocks' => array('through' => 'SiteBlock', 'foreignKey' => 'site')
	);

	protected $hasContent = array(
		'content' => array('through' => 'SiteContent', 'foreignKey' => 'site')
	);

	protected $hasMany = array(
		'settings'     => array('model' => 'SiteSettings', 'foreignKey' => 'site'),
		'assetFolders' => array('model' => 'AssetFolder', 'foreignKey' => 'site'),
		'routes'       => array('model' => 'Route', 'foreignKey' => 'site'),
		'sections'     => array('model' => 'Section', 'foreignKey' => 'site')
	);
}
