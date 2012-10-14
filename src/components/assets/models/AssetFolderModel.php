<?php
namespace Blocks;

/**
 *
 */
class AssetFolderModel extends BaseModel
{
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
		);
	}

	/**
	 * @return AssetSourceModel|null
	 */
	public function getSource()
	{
		return blx()->assetSources->getSourceById($this->sourceId);
	}
}
