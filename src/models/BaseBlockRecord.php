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
			'name'          => AttributeType::Name,
			'handle'        => array(AttributeType::Handle, 'reservedWords' => 'id,dateCreated,dateUpdated,uid,title'),
			'instructions'  => AttributeType::Text,
			'required'      => AttributeType::Boolean,
			'translatable'  => AttributeType::Boolean,
			'class'         => AttributeType::ClassName,
			'blockSettings' => AttributeType::Json,
			'sortOrder'     => AttributeType::SortOrder,
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
