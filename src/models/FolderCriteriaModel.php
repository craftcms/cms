<?php
namespace Blocks;

/**
 * Folders parameters
 */
class FolderCriteriaModel extends BaseModel
{

	/**
	 * Has no parent folders.
	 */
	const AssetsNoParent = -1;

	/**
	 * If no parentId is being set, set it to false instead of null, since null means something in this context.
	 *
	 * @param mixed|null $attributes
	 */
	public function __construct($attributes)
	{
		// Default this to false.
		$this->parentId = false;

		parent::__construct($attributes);

		// Set to null, if we're looking for folders with no parents.
		if (isset($attributes['parentId']) && $attributes['parentId'] == self::AssetsNoParent)
		{
			$this->parentId = null;
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
