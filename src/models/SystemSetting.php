<?php
namespace Blocks;

/**
 *
 */
class SystemSetting extends BaseSettingsModel
{
	protected $tableName = 'systemsettings';

	protected $indexes = array(
		array('columns' => array('category','name'), 'unique' => true)
	);

	/**
	 * Init
	 */
	function init()
	{
		// Add the `category` attribute
		$this->attributes['category'] = array('type' => AttributeType::Char, 'maxLength' => 15, 'required' => true);
	}
}
