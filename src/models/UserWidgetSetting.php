<?php
namespace Blocks;

/**
 *
 */
class UserWidgetSetting extends BaseSettingsModel
{
	protected $tableName = 'userwidgetsettings';
	protected $model = 'UserWidget';
	protected $foreignKey = 'widget';
}
