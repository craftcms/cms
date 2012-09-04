<?php
namespace Blocks;

/**
 *
 */
class Route extends BaseModel
{
	public function getTableName()
	{
		return 'routes';
	}

	protected function getProperties()
	{
		return array(
			'urlParts'   => array(PropertyType::Varchar, 'required' => true),
			'urlPattern' => array(PropertyType::Varchar, 'required' => true),
			'template'   => array(PropertyType::Varchar, 'required' => true),
			'sortOrder'  => PropertyType::SortOrder,
		);
	}

	protected function getIndexes()
	{
		return array(
			array('columns' => array('urlPattern'), 'unique' => true),
		);
	}

	public function scopes()
	{
		return array(
			'ordered' => array(
				'order' => 'sortOrder ASC'
			)
		);
	}
}
