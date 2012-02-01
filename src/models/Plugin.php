<?php
namespace Blocks;

/**
 *
 */
class Plugin extends BaseModel
{
	protected $tableName = 'plugins';

	protected $attributes = array(
		'class'   => AttributeType::ClassName,
		'version' => AttributeType::Version,
		'enabled' => array('type' => AttributeType::Boolean, 'default' => true)
	);

	protected $hasMany = array(
		'settings' => array('model' => 'PluginSetting', 'foreignKey' => 'plugin')
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
