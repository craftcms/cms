<?php
namespace Blocks;

/**
 *
 */
class SystemSetting extends BaseModel
{
	public function getTableName()
	{
		return 'systemsettings';
	}

	protected function getProperties()
	{
		return array(
			'name'     => array(PropertyType::Varchar, 'maxLength' => 100, 'required' => true),
			'value'    => PropertyType::Text,
			'category' => array(PropertyType::Char, 'maxLength' => 15, 'required' => true),
		);
	}

	protected function getIndexes()
	{
		return array(
			array('columns' => array('category','name'), 'unique' => true)
		);
	}
}
