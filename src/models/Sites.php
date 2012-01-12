<?php

/**
 *
 */
class Sites extends BaseModel
{
	protected $attributes = array(
		'handle' => array('type' => AttributeType::String, 'maxSize' => 150, 'required' => true),
		'label'  => array('type' => AttributeType::String, 'maxSize' => 500, 'required' => true),
		'url'    => array('type' => AttributeType::String, 'maxSize' => 250, 'required' => true)
	);

	protected $hasBlocks = array(
		'blocks' => array('through' => 'SiteBlocks', 'foreignKey' => 'site')
	);

	protected $hasContent = array(
		'content' => array('through' => 'SiteContent', 'foreignKey' => 'site')
	);

	protected $hasMany = array(
		'settings'     => array('model' => 'SiteSettings', 'foreignKey' => 'site'),
		'assetFolders' => array('model' => 'AssetFolders', 'foreignKey' => 'site'),
		'routes'       => array('model' => 'Routes', 'foreignKey' => 'site'),
		'sections'     => array('model' => 'Sections', 'foreignKey' => 'site')
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
