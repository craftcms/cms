<?php
namespace Craft;

/**
 * Class AssetFolderModel
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.0
 */
class AssetFolderModel extends BaseModel
{
	// Properties
	// =========================================================================

	/**
	 * @var array
	 */
	private $_children = null;

	// Public Methods
	// =========================================================================

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
	 * Manually set the child folders.
	 *
	 * @param array $children
	 */
	public function setChildren(array $children)
	{
		$this->_children = $children;
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

	/**
	 * @inheritDoc BaseModel::setAttribute()
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return bool
	 */
	public function setAttribute($name, $value)
	{
		if ($name == 'path' && !empty($value))
		{
			$value = rtrim($value, '/').'/';
		}
		return parent::setAttribute($name, $value);
	}

	/**
	 * @inheritDoc BaseModel::getAttribute()
	 *
	 * @param string $name
	 * @param bool   $flattenValue
	 *
	 * @return mixed
	 */
	public function getAttribute($name, $flattenValue = false)
	{
		$value = parent::getAttribute($name, $flattenValue);

		if ($name == 'path' && !empty($value))
		{
			$value = rtrim($value, '/').'/';
		}

		return $value;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
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
