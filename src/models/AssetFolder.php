<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\enums\AttributeType;

/**
 * The AssetFolder model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetFolder extends Model
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
	 * @return AssetSource|null
	 */
	public function getSource()
	{
		return Craft::$app->assetSources->getSourceById($this->sourceId);
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
			$this->_children = Craft::$app->assets->findFolders(['parentId' => $this->id]);
		}

		return $this->_children;
	}

	/**
	 * @return AssetFolder|null
	 */
	public function getParent()
	{
		if (!$this->parentId)
		{
			return null;
		}

		return Craft::$app->assets->getFolderById($this->parentId);
	}

	/**
	 * Add a child folder manually.
	 *
	 * @param AssetFolder $folder
	 *
	 * @return null
	 */
	public function addChild(AssetFolder $folder)
	{
		if (is_null($this->_children))
		{
			$this->_children = [];
		}

		$this->_children[] = $folder;
	}

	/**
	 * @inheritDoc Model::setAttribute()
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
	 * @inheritDoc Model::getAttribute()
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
	 * @inheritDoc Model::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'id'       => AttributeType::Number,
			'parentId' => AttributeType::Number,
			'sourceId' => AttributeType::Number,
			'name'     => AttributeType::String,
			'path'     => AttributeType::String,
		];
	}
}
