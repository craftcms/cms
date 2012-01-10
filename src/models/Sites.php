<?php

class Sites extends BlocksModel
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

	protected static $hasSettings = true;
	protected static $hasContent = true;
	protected static $hasCustomBlocks = true;

	protected static $hasMany = array(
		'assetFolders' => array('model' => 'AssetFolders', 'foreignKey' => 'site'),
		'routes'       => array('model' => 'Routes', 'foreignKey' => 'site'),
		'sections'     => array('model' => 'Sections', 'foreignKey' => 'site')
	);

	protected static $attributes = array(
		'handle' => array('type' => AttributeType::String, 'maxSize' => 150, 'required' => true),
		'label'  => array('type' => AttributeType::String, 'maxSize' => 500, 'required' => true),
		'url'    => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true)
	);
}
