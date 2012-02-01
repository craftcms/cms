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
}
