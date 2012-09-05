<?php
namespace Blocks;

/**
 *
 */
class SystemSettingsRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'systemsettings';
	}

	protected function getProperties()
	{
		return array(
			'category' => array(PropertyType::Varchar, 'maxLength' => 15, 'unique' => true, 'required' => true),
			'settings' => PropertyType::Json,
		);
	}
}
