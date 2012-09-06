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
			'urlParts'   => array(AttributeType::Varchar, 'required' => true),
			'urlPattern' => array(AttributeType::Varchar, 'required' => true),
			'template'   => array(AttributeType::Varchar, 'required' => true),
			'sortOrder'  => AttributeType::SortOrder,
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('urlPattern'), 'unique' => true),
		);
	}
}
