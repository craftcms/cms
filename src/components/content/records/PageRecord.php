<?php
namespace Blocks;

/**
 *
 */
class PageRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'pages';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'title'     => array(AttributeType::Name, 'required' => true),
			'uri'       => array(AttributeType::String, 'required' => true),
			'template'  => AttributeType::Template,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'blocks'  => array(static::HAS_MANY, 'PageBlockRecord', 'pageId', 'order' => 'blocks.sortOrder'),
			'content' => array(static::HAS_ONE, 'PageContentRecord', 'pageId'),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('uri'), 'unique' => true),
		);
	}
}
