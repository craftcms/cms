<?php
namespace Blocks;

/**
 *
 */
class SystemSettingsRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'systemsettings';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'category' => array(AttributeType::String, 'maxLength' => 15, 'unique' => true, 'required' => true),
			'settings' => AttributeType::Mixed,
		);
	}
}
