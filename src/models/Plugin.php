<?php
namespace Blocks;

/**
 *
 */
class Plugin extends BaseModel
{
	protected $tableName = 'plugins';

	protected $attributes = array(
		'name'    => array('type' => AttributeType::String, 'maxLength' => 50, 'unique' => true),
		'version' => array('type' => AttributeType::String, 'maxLength' => 15),
		'enabled' => array('type' => AttributeType::Boolean, 'default' => true, 'required' => true, 'unsigned' => true)
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
