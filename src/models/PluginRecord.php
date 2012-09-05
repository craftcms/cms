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

	protected function getProperties()
	{
		return array(
			'class'    => PropertyType::ClassName,
			'version'  => PropertyType::Version,
			'enabled'  => PropertyType::Boolean,
			'settings' => PropertyType::Text,
		);
	}
}
