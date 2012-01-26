<?php

/**
 *
 */
class bSystemSetting extends bBaseSettingsModel
{
	protected $tableName = 'systemsettings';

	/**
	 * Add a category attribute to system settings as well as a unique index on category and key.
	 */
	function init()
	{
		parent::init();
		$this->attributes['category'] = array('type' => bAttributeType::String, 'maxLength' => 250, 'required' => true);
		$this->indexes[] = array('column' => 'category, key', 'unique' => true);
	}

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
