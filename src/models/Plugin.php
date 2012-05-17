<?php
namespace Blocks;

/**
 *
 */
class Plugin extends BaseModel
{
	protected $tableName = 'plugins';
	protected $settingsTableName = 'pluginsettings';
	protected $foreignKeyName = 'plugin_id';
	public $hasSettings = true;

	protected $attributes = array(
		'class'      => AttributeType::ClassName,
		'version'    => AttributeType::Version,
		'enabled'    => array('type' => AttributeType::Boolean)
	);

	protected $hasMany = array(
		'settings' => array('model' => 'PluginSetting', 'foreignKey' => 'plugin')
	);
}
