<?php

/**
 *
 */
class AssetFolders extends BaseModel
{
	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	protected $hasMany = array(
		'assets' => array('model' => 'Assets', 'foreignKey' => 'folder')
	);

	protected $belongsTo = array(
		'site' => array('model' => 'Sites', 'required' => true)
	);

	protected $attributes = array(
		'name' => array('type' => 'varchar', 'size' => 1000, 'required' => true),
		'path' => array('type' => 'varchar', 'size' => 1000, 'required' => true)
	);
}
