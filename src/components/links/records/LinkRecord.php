<?php
namespace Blocks;

/**
 *
 */
class LinkRecord extends BaseRecord
{
	/**
	 * @return array
	 */
	public function getTableName()
	{
		return 'links';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'leftSortOrder'  => AttributeType::SortOrder,
			'rightSortOrder' => AttributeType::SortOrder,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'criteria'   => array(static::BELONGS_TO, 'LinkCriteriaRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'leftEntry'  => array(static::BELONGS_TO, 'EntryRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'rightEntry' => array(static::BELONGS_TO, 'EntryRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('criteriaId', 'leftEntryId', 'rightEntryId'), 'unique' => true),
		);
	}
}
