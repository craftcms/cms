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
			'leftEntityId' => array(AttributeType::Number, 'unsigned' => true),
			'rightEntityId' => array(AttributeType::Number, 'required' => true, 'unsigned' => true),
			'leftSortOrder' => AttributeType::SortOrder,
			'rightSortOrder' => AttributeType::SortOrder,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'criteria' => array(static::BELONGS_TO, 'LinkCriteriaRecord'),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('criteriaId', 'leftEntityId', 'rightEntityId'), 'unique' => true),
		);
	}
}
