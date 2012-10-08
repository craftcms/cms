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
			'blockId'    => array(AttributeType::Number, 'required' => true, 'unsigned' => true),
			'parentType' => array(AttributeType::ClassName, 'required' => true),
			'parentId'   => array(AttributeType::Number, 'required' => true, 'unsigned' => true),
			'childType'  => array(AttributeType::ClassName, 'required' => true),
			'childId'    => array(AttributeType::Number, 'required' => true, 'unsigned' => true),
			'sortOrder'  => AttributeType::SortOrder,
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('blockId', 'parentType', 'parentId', 'childType', 'childId'), 'unique' => true),
		);
	}
}
