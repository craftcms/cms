<?php
namespace Craft;

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
			'criteria'     => array(static::BELONGS_TO, 'LinkCriteriaRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'leftElement'  => array(static::BELONGS_TO, 'ElementRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'rightElement' => array(static::BELONGS_TO, 'ElementRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('criteriaId', 'leftElementId', 'rightElementId'), 'unique' => true),
		);
	}
}
