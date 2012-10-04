<?php
namespace Blocks;

/**
 *
 */
class PluginRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'plugins';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'class'    => array(AttributeType::ClassName, 'required' => true),
			'version'  => array(AttributeType::Version, 'required' => true),
			'enabled'  => AttributeType::Bool,
			'settings' => array(AttributeType::String, 'column' => ColumnType::Text),
		);
	}
}
