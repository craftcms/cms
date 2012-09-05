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
	protected function getProperties()
	{
		return array(
			'name'         => PropertyType::Name,
			'handle'       => array(PropertyType::Handle, 'reservedWords' => 'id,dateCreated,dateUpdated,uid,title'),
			'instructions' => PropertyType::Text,
			'required'     => PropertyType::Boolean,
			'translatable' => PropertyType::Boolean,
			'class'        => PropertyType::ClassName,
			'settings'     => PropertyType::Json,
			'sortOrder'    => PropertyType::SortOrder,
		);
	}

	/**
	 * @return array
	 */
	protected function getIndexes()
	{
		return array(
			array('columns' => 'handle', 'unique' => true)
		);
	}
}
