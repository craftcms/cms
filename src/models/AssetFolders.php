<?php

/**
 *
 */
class AssetFolders extends BaseModel
{
	protected $attributes = array(
		'name' => array('type' => AttributeType::String, 'size' => 1000, 'required' => true),
		'path' => array('type' => AttributeType::String, 'size' => 1000, 'required' => true)
	);

	protected $belongsTo = array(
		'site' => array('model' => 'Sites', 'required' => true)
	);

	protected $hasMany = array(
		'assets' => array('model' => 'Assets', 'foreignKey' => 'folder')
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
