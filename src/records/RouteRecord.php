<?php
namespace Craft;

/**
 *
 */
class RouteRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'routes';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'urlParts'   => array(AttributeType::String, 'required' => true),
			'urlPattern' => array(AttributeType::String, 'required' => true),
			'template'   => array(AttributeType::String, 'required' => true),
			'sortOrder'  => AttributeType::SortOrder,
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('urlPattern'), 'unique' => true),
		);
	}
}
