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
