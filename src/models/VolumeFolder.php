<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\base\Volume;
use craft\base\VolumeInterface;

/**
 * The VolumeFolder model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class VolumeFolder extends Model
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
     * @var VolumeFolder[]
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
            [['id', 'parentId', 'volumeId'], 'number', 'integerOnly' => true],
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
     * @return VolumeInterface|null
     */
    public function getVolume()
    {
        return Craft::$app->getVolumes()->getVolumeById($this->volumeId);
    }

    /**
     * Set the child folders.
     *
     * @param VolumeFolder[] $children
     */
    public function setChildren(array $children)
    {
        $this->_children = $children;
    }

    /**
     * Get this folder's children.
     *
     * @return VolumeFolder[]
     */
    public function getChildren()
    {
        if (is_null($this->_children)) {
            $this->_children = Craft::$app->getAssets()->findFolders(['parentId' => $this->id]);
        }

        return $this->_children;
    }

    /**
     * @return VolumeFolder|null
     */
    public function getParent()
    {
        if (!$this->parentId) {
            return null;
        }

        return Craft::$app->getAssets()->getFolderById($this->parentId);
    }

    /**
     * Add a child folder manually.
     *
     * @param VolumeFolder $folder
     *
     * @return void
     */
    public function addChild(VolumeFolder $folder)
    {
        if (is_null($this->_children)) {
            $this->_children = [];
        }

        $this->_children[] = $folder;
    }
}
