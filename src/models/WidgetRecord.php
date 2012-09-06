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

	protected function defineAttributes()
	{
		return array(
			'class'      => AttributeType::ClassName,
			'sortOrder'  => AttributeType::SortOrder,
			'settings'   => AttributeType::Text,
		);
	}

	protected function defineRelations()
	{
		return array(
			'user'   => array(static::BELONGS_TO, 'UserRecord', 'userId', 'required' => true),
			'plugin' => array(static::BELONGS_TO, 'PluginRecord', 'pluginId'),
		);
	}
}
