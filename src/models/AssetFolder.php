<?php
namespace Blocks;

/**
 *
 */
class AssetFolder extends Model
{
	protected $tableName = 'assetfolders';

	protected $attributes = array(
		'name' => AttributeType::Name,
		'path' => array('type' => AttributeType::Varchar, 'size' => 1000, 'required' => true)
	);

	protected $belongsTo = array(
		'site' => array('model' => 'Site', 'required' => true)
	);

	protected $hasMany = array(
		'assets' => array('model' => 'Asset', 'foreignKey' => 'folder')
	);

	protected $indexes = array(
		array('columns' => array('site_id','name'), 'unique' => true)
	);
}
