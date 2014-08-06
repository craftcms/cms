<?php
namespace Craft;

/**
 * Class AssetFolderModel
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.0
 */
class AssetFolderModel extends BaseModel
{
	////////////////////
	// PROPERTIES
	////////////////////

	/**
	 * @var array
	 */
	private $_children = null;

	////////////////////
	// PUBLIC METHODS
	////////////////////

	/**
	 * Use the folder name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->name;
	}

	/**
	 * @return AssetSourceModel|null
	 */
	public function getSource()
	{
		return craft()->assetSources->getSourceById($this->sourceId);
	}

	/**
	 * Get this folder's children.
	 *
	 * @return array|null
	 */
	public function getChildren()
	{
		if (is_null($this->_children))
		{
			$this->_children = craft()->assets->findFolders(array('parentId' => $this->id));
		}

		return $this->_children;
	}

	/**
	 * @return AssetFolderModel|null
	 */
	public function getParent()
	{
		if (!$this->parentId)
		{
			return null;
		}

		return craft()->assets->getFolderById($this->parentId);
	}

	/**
	 * Add a child folder manually.
	 *
	 * @param AssetFolderModel $folder
	 *
	 * @return null
	 */
	public function addChild(AssetFolderModel $folder)
	{
		if (is_null($this->_children))
		{
			$this->_children = array();
		}

		$this->_children[] = $folder;
	}

	////////////////////
	// PROTECTED METHODS
	////////////////////

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'       => AttributeType::Number,
			'parentId' => AttributeType::Number,
			'sourceId' => AttributeType::Number,
			'name'     => AttributeType::String,
			'path'     => AttributeType::String,
		);
	}
}
