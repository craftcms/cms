<?php
namespace Blocks;

/**
 *
 */
class AssetFolderModel extends BaseModel
{
	/**
	 * Breadcrumbs for this folder (array of id => folder name ordered by depth)
	 * @var array
	 */
	private $_breadCrumbs = null;

	/**
	 * Use the folder name as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->name;
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
		);
	}

	/**
	 * @return AssetSourceModel|null
	 */
	public function getSource()
	{
		return blx()->assetSources->getSourceById($this->sourceId);
	}

	/**
	 * Return this folder's breadcrumbs
	 * @return array
	 */
	public function getBreadCrumbs()
	{
		return array();
	}
}
