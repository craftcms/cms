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
			'url_parts'   => array(PropertyType::Varchar, 'required' => true),
			'url_pattern' => array(PropertyType::Varchar, 'required' => true),
			'template'    => array(PropertyType::Varchar, 'required' => true),
			'sortOrder'   => PropertyType::SortOrder,
		);
	}

	protected function getIndexes()
	{
		return array(
			array('columns' => array('url_pattern'), 'unique' => true),
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
