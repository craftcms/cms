<?php
namespace Craft;

/**
 * Folders parameters
 */
class FolderCriteriaModel extends BaseModel
{

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'       => AttributeType::Number,
			'parentId' => array(AttributeType::Number, 'default' => false),
			'sourceId' => AttributeType::Number,
			'name'     => AttributeType::String,
			'path'     => AttributeType::String,
			'order'    => array(AttributeType::String, 'default' => 'name asc'),
			'offset'   => AttributeType::Number,
			'limit'    => AttributeType::Number,
		);
	}
}
