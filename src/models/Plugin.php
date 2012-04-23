<?php
namespace Blocks;

/**
 *
 */
class Plugin extends Model
{
	// Model properties
	protected $tableName = 'plugins';
	protected $settingsTableName = 'pluginsettings';
	protected $foreignKeyName = 'plugin_id';
	protected $classSuffix = 'Plugin';
	public $hasSettings = true;

	protected $attributes = array(
		'class'      => AttributeType::ClassName,
		'version'    => AttributeType::Version,
		'enabled'    => array('type' => AttributeType::Boolean, 'default' => true)
	);

	protected $hasMany = array(
		'settings' => array('model' => 'PluginSetting', 'foreignKey' => 'plugin')
	);
}
