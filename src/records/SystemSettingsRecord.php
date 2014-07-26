<?php
namespace Craft;

/**
 * Class SystemSettingsRecord
 *
 * @package craft.app.records
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
	protected function defineAttributes()
	{
		return array(
			'category' => array(AttributeType::String, 'maxLength' => 15, 'required' => true),
			'settings' => AttributeType::Mixed,
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => 'category', 'unique' => true),
		);
	}
}
