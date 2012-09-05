<?php
namespace Blocks;

/**
 *
 */
class WidgetRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'widgets';
	}

	protected function getProperties()
	{
		return array(
			'class'      => PropertyType::ClassName,
			'sortOrder'  => PropertyType::SortOrder,
			'settings'   => PropertyType::Text,
		);
	}

	protected function getRelations()
	{
		return array(
			'user'   => array(static::BELONGS_TO, 'UserRecord', 'userId', 'required' => true),
			'plugin' => array(static::BELONGS_TO, 'PluginRecord', 'pluginId'),
		);
	}
}
