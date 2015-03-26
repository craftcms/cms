<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\base\Volume;

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
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var integer Parent ID
	 */
	public $parentId;

	/**
	 * @var integer Volume ID
	 */
	public $volumeId;

	/**
	 * @var string Name
	 */
	public $name;

	/**
	 * @var string Path
	 */
	public $path;


	/**
	 * @var array
	 */
	private $_children = null;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['parentId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['volumeId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['id', 'parentId', 'volumeId', 'name', 'path'], 'safe', 'on' => 'search'],
		];
	}

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
	 * @return Volume|null
	 */
	public function getVolume()
	{
		return Craft::$app->volumes->getVolumeById($this->volumeId);
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
}
