<?php
namespace Blocks;

/**
 * Global content model class
 *
 * Used for transporting entry data throughout the system.
 */
class GlobalContentModel extends BaseEntityModel
{
	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'id'       => AttributeType::Number,
			'language' => AttributeType::Language,
		);
	}

	/**
	 * Gets the blocks.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getBlocks()
	{
		return blx()->globalBlocks->getAllBlocks();
	}

	/**
	 * Gets the content.
	 *
	 * @access protected
	 * @return array|\CModel
	 */
	protected function getContent()
	{
		return blx()->globalBlocks->getGlobalContentRecord($this);
	}
}
