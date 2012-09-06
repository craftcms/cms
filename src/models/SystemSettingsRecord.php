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

	public function defineAttributes()
	{
		return array(
			'category' => array(AttributeType::Varchar, 'maxLength' => 15, 'unique' => true, 'required' => true),
			'settings' => AttributeType::Json,
		);
	}
}
