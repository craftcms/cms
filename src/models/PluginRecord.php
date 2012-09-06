<?php
namespace Blocks;

/**
 *
 */
class PluginRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'plugins';
	}

	public function defineAttributes()
	{
		return array(
			'class'    => AttributeType::ClassName,
			'version'  => AttributeType::Version,
			'enabled'  => AttributeType::Boolean,
			'settings' => AttributeType::Text,
		);
	}
}
