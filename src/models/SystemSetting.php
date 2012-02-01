<?php
namespace Blocks;

/**
 *
 */
class SystemSetting extends BaseSettingsModel
{
	protected $tableName = 'systemsettings';

	protected $indexes = array(
		array('columns' => array('category','key'), 'unique' => true)
	);

	/**
	 * Init
	 */
	function init()
	{
		// Add the `category` attribute
		$this->attributes['category'] = array('type' => AttributeType::Char, 'maxLength' => 15, 'required' => true);
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
