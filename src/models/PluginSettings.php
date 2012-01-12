<?php

/**
 *
 */
class PluginSettings extends BaseSettingsModel
{
	protected $model = 'Plugins';
	protected $foreignKey = 'plugin';

	/**
	 * Returns an instance of the specified model
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $class
	 *
	 * @return object The model instance
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
