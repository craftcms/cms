<?php
namespace Blocks;

/**
 *
 */
class RouteRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'routes';
	}

	public function defineAttributes()
	{
		return array(
			'urlParts'   => array(AttributeType::String, 'required' => true),
			'urlPattern' => array(AttributeType::String, 'required' => true),
			'template'   => array(AttributeType::String, 'required' => true),
			'sortOrder'  => array(AttributeType::SortOrder, 'required' => true),
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('urlPattern'), 'unique' => true),
		);
	}
}
