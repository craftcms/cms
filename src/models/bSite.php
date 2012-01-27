<?php

/**
 *
 */
class bSite extends bBaseModel
{
	protected $tableName = 'sites';

	protected $attributes = array(
		'name'     => array('type' => bAttributeType::String, 'maxLength' => 500, 'required' => true, 'unique' => true),
		'handle'   => array('type' => bAttributeType::String, 'maxLength' => 150, 'required' => true, 'unique' => true),
		'url'      => array('type' => bAttributeType::String, 'required' => true),
		'enabled'  => array('type' => bAttributeType::Boolean, 'required' => true, 'deafult' => true, 'unsigned' => true)
	);

	protected $hasBlocks = array(
		'blocks' => array('through' => 'bSiteBlock', 'foreignKey' => 'site')
	);

	protected $hasContent = array(
		'content' => array('through' => 'bSiteContent', 'foreignKey' => 'site')
	);

	protected $hasMany = array(
		'settings'     => array('model' => 'bSiteSettings', 'foreignKey' => 'site'),
		'assetFolders' => array('model' => 'bAssetFolder', 'foreignKey' => 'site'),
		'routes'       => array('model' => 'bRoute', 'foreignKey' => 'site'),
		'sections'     => array('model' => 'bSection', 'foreignKey' => 'site')
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
