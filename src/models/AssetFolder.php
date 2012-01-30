<?php
namespace Blocks;

/**
 *
 */
class AssetFolder extends BaseModel
{
	protected $tableName = 'assetfolders';

	protected $attributes = array(
		'name' => array('type' => AttributeType::String, 'size' => 1000, 'required' => true),
		'path' => array('type' => AttributeType::String, 'size' => 1000, 'required' => true)
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

	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
