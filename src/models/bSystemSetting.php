<?php

/**
 *
 */
class bSystemSetting extends bBaseSettingsModel
{
	protected $tableName = 'systemsettings';

	/**
	 *
	 */
	function init()
	{
		parent::init();
		$this->attributes['category'] = array('type' => bAttributeType::String, 'maxLength' => 250, 'required' => true);
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
