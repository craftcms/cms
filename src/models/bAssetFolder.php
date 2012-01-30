<?php

/**
 *
 */
class bAssetFolder extends bBaseModel
{
	protected $tableName = 'assetfolders';

	protected $attributes = array(
		'name' => bAttributeType::Name,
		'path' => array('type' => bAttributeType::Varchar, 'size' => 1000, 'required' => true)
	);

	protected $belongsTo = array(
		'site' => array('model' => 'bSite', 'required' => true)
	);

	protected $hasMany = array(
		'assets' => array('model' => 'bAsset', 'foreignKey' => 'folder')
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
