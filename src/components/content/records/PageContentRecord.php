<?php
namespace Blocks;

/**
 *
 */
class PageContentRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'pagecontent';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$attributes['language'] = array(AttributeType::Language, 'required' => true);
		}

		$attributes['content'] = array(AttributeType::Mixed, 'column' => ColumnType::MediumText);

		return $attributes;
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'page' => array(static::BELONGS_TO, 'PageRecord', 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			$indexes[] = array('columns' => array('language', 'pageId'), 'unique' => true);
		}
		else
		{
			$indexes[] = array('columns' => array('pageId'), 'unique' => true);
		}

		return $indexes;
	}
}
