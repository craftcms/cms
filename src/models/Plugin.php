<?php
namespace Blocks;

/**
 *
 */
class Plugin extends BaseModel
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
