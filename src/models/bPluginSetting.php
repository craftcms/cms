<?php

/**
 *
 */
class bPluginSetting extends bBaseSettingsModel
{
	protected $tableName = 'pluginsettings';
	protected $model = 'bPlugin';
	protected $foreignKey = 'plugin';

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
