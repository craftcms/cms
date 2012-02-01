<?php
namespace Blocks;

/**
 *
 */
class PluginSetting extends BaseSettingsModel
{
	protected $tableName = 'pluginsettings';
	protected $model = 'Plugin';
	protected $foreignKey = 'plugin';
}
