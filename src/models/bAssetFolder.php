<?php

/**
 *
 */
class bAssetFolder extends bBaseModel
{
	protected $tableName = 'assetfolders';

	protected $attributes = array(
		'name' => array('type' => bAttributeType::String, 'size' => 1000, 'required' => true),
		'path' => array('type' => bAttributeType::String, 'size' => 1000, 'required' => true)
	);

	protected $belongsTo = array(
		'site' => array('model' => 'bSite', 'required' => true)
	);

	protected $hasMany = array(
		'assets' => array('model' => 'bAsset', 'foreignKey' => 'folder')
	);

	protected $indexes = array(
		array('column' => 'name,site_id', 'unique' => true)
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
