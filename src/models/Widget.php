<?php
namespace Blocks;

/**
 *
 */
class Widget extends BaseModel
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
			'user'   => array(static::BELONGS_TO, 'User', 'userId', 'required' => true),
			'plugin' => array(static::BELONGS_TO, 'Plugin', 'pluginId'),
		);
	}
}
