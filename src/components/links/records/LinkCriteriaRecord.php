<?php
namespace Blocks;

/**
 *
 */
class LinkCriteriaRecord extends BaseRecord
{
	/**
	 * @return array
	 */
	public function getTableName()
	{
		return 'linkcriteria';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'ltrHandle'      => AttributeType::String,
			'rtlHandle'      => AttributeType::String,
			'leftEntryType'  => array(AttributeType::ClassName, 'required' => true),
			'rightEntryType' => array(AttributeType::ClassName, 'required' => true),
			'leftSettings'   => AttributeType::Mixed,
			'rightSettings'  => AttributeType::Mixed,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'links' => array(static::HAS_MANY, 'LinkRecord', 'criteriaId'),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('ltrHandle')),
			array('columns' => array('rtlHandle')),
		);
	}
}
