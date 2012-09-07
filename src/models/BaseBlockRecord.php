<?php
namespace Blocks;

/**
 * Block record base class
 *
 * @abstract
 */
abstract class BaseBlockRecord extends BaseRecord
{
	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'name'          => array(AttributeType::Name, 'required' => true),
			'handle'        => array(AttributeType::Handle, 'required' => true),
			'instructions'  => array(AttributeType::String, 'column' => ColumnType::Text),
			'required'      => AttributeType::Bool,
			'translatable'  => AttributeType::Bool,
			'class'         => array(AttributeType::ClassName, 'required' => true),
			'blockSettings' => AttributeType::Mixed,
			'sortOrder'     => array(AttributeType::SortOrder, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => 'handle', 'unique' => true)
		);
	}
}
