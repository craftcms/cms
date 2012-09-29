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

	public function defineAttributes()
	{
		return array(
			'type'      => array(AttributeType::ClassName, 'required' => true),
			'sortOrder' => AttributeType::SortOrder,
			'settings'  => AttributeType::Array,
		);
	}

	public function defineRelations()
	{
		return array(
			'user'   => array(static::BELONGS_TO, 'UserRecord', 'userId', 'required' => true),
			'plugin' => array(static::BELONGS_TO, 'PluginRecord', 'pluginId'),
		);
	}
}
