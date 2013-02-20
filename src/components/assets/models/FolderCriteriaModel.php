<?php
namespace Blocks;

/**
 * Folders parameters
 */
class FolderCriteriaModel extends BaseModel
{
	/**
	 * If no parentId is being set, set it to false instead of null, since null means something in this context.
	 * 
	 * @param mixed|null $attributes
	 */
	public function __construct($attributes)
	{
		parent::__construct($attributes);
		if (!isset($attributes['parentId']))
		{
			$this->parentId = false;
		}
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'id'       => AttributeType::Number,
			'parentId' => AttributeType::Number,
			'sourceId' => AttributeType::Number,
			'name'     => AttributeType::String,
			'fullPath' => AttributeType::String,
			'order'    => array(AttributeType::String, 'default' => 'name asc'),
			'offset'   => AttributeType::Number,
			'limit'    => AttributeType::Number,
		);
	}
}
