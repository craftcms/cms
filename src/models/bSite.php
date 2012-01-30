<?php

/**
 *
 */
class bSite extends bBaseModel
{
	protected $tableName = 'sites';

	protected $attributes = array(
		'name'     => bAttributeType::Name,
		'handle'   => bAttributeType::Handle,
		'url'      => array('type' => bAttributeType::Varchar, 'required' => true),
		'enabled'  => array('type' => bAttributeType::Boolean, 'default' => true)
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
