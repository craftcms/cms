<?php
namespace Blocks;

/**
 * Block model base class
 *
 * @abstract
 */
abstract class BaseBlockModel extends BaseModel
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
