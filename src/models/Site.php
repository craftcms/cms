<?php
namespace Blocks;

/**
 *
 */
class Site extends BaseModel
{
	protected $tableName = 'sites';

	protected $attributes = array(
		'name'     => array('type' => AttributeType::String, 'maxLength' => 500, 'required' => true, 'unique' => true),
		'handle'   => array('type' => AttributeType::String, 'maxLength' => 150, 'required' => true, 'unique' => true),
		'url'      => array('type' => AttributeType::String, 'required' => true),
		'enabled'  => array('type' => AttributeType::Boolean, 'required' => true, 'default' => true, 'unsigned' => true)
	);

	protected $hasBlocks = array(
		'blocks' => array('through' => 'SiteBlock', 'foreignKey' => 'site')
	);

	protected $hasContent = array(
		'content' => array('through' => 'SiteContent', 'foreignKey' => 'site')
	);

	protected $hasMany = array(
		'settings'     => array('model' => 'SiteSettings', 'foreignKey' => 'site'),
		'assetFolders' => array('model' => 'AssetFolder', 'foreignKey' => 'site'),
		'routes'       => array('model' => 'Route', 'foreignKey' => 'site'),
		'sections'     => array('model' => 'Section', 'foreignKey' => 'site')
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
