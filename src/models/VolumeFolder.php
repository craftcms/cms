<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\base\VolumeInterface;
use craft\helpers\Html;
use craft\volumes\Temp;
use yii\base\InvalidConfigException;

/**
 * The VolumeFolder model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class VolumeFolder extends Model
{
    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|string|null Parent ID
     */
    public $parentId;

    /**
     * @var int|null Volume ID
     */
    public $volumeId;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Path
     */
    public $path;

    /**
     * @var string|null UID
     */
    public $uid;

    /**
     * @var VolumeFolder[]|null
     */
    private $_children;

    /**
     * @var bool|null
     */
    private $_hasChildren;

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'parentId', 'volumeId'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * Use the folder name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->name ?: static::class;
    }

    /**
     * @return VolumeInterface
     * @throws InvalidConfigException if [[volumeId]] is invalid
     */
    public function getVolume(): VolumeInterface
    {
        if ($this->volumeId === null) {
            return new Temp();
        }

        if (($volume = Craft::$app->getVolumes()->getVolumeById($this->volumeId)) === null) {
            throw new InvalidConfigException('Invalid volume ID: ' . $this->volumeId);
        }

        return $volume;
    }

    /**
     * Returns info about the folder for an element index’s source path configuration.
     *
     * @return array|null
     * @since 3.8.0
     */
    public function getSourcePathInfo(): ?array
    {
        if (!$this->volumeId) {
            return null;
        }

        $volume = $this->getVolume();
        $userSession = Craft::$app->getUser();
        $canView = $userSession->checkPermission("viewVolume:$volume->uid");
        $canCreate = $userSession->checkPermission("createFoldersInVolume:$volume->uid");
        $canDelete = $userSession->checkPermission("deletePeerFilesInVolume:$volume->uid");
        $canMove = $canDelete && $userSession->checkPermission("editPeerFilesInVolume:$volume->uid");

        $info = [
            'uri' => sprintf('assets/%s%s', $volume->handle, $this->path ? sprintf('/%s', trim($this->path, '/')) : ''),
            'folderId' => (int)$this->id,
            'hasChildren' => $this->getHasChildren(),
            'canView' => $canView,
            'canCreate' => $canCreate,
            'canMoveSubItems' => $canMove,
        ];

        // Is this a root folder?
        if (!$this->parentId) {
            $info += [
                'key' => "folder:$this->uid",
                'icon' => 'home',
                'label' => Craft::t('app', '{volume} root', [
                    'volume' => Html::encode(Craft::t('site', $volume->name)),
                ]),
                'handle' => $volume->handle,
            ];
        } else {
            $canRename = $canCreate & $userSession->checkPermission("deleteFilesAndFoldersInVolume:$volume->uid");

            $info += [
                'key' => "folder:$this->uid",
                'label' => Html::encode($this->name),
                'criteria' => [
                    'folderId' => $this->id,
                ],
                'canRename' => $canRename,
                'canMove' => $canMove,
                'canDelete' => $canDelete,
            ];
        }

        return $info;
    }

    /**
     * Returns whether the folder has any child folders.
     *
     * @return bool
     * @since 3.8.0
     */
    public function getHasChildren(): bool
    {
        if (isset($this->_children)) {
            return !empty($this->_children);
        }

        if (!isset($this->_hasChildren)) {
            $this->_hasChildren = Craft::$app->getAssets()->foldersExist(['parentId' => $this->id]);
        }

        return $this->_hasChildren;
    }

    /**
     * Sets whether the folder has any child folders.
     *
     * @param bool $value
     * @since 3.8.0
     */
    public function setHasChildren(bool $value)
    {
        $this->_hasChildren = $value;
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
    public function getChildren(): array
    {
        if ($this->_children !== null) {
            return $this->_children;
        }

        return $this->_children = Craft::$app->getAssets()->findFolders(['parentId' => $this->id]);
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
     */
    public function addChild(VolumeFolder $folder)
    {
        if ($this->_children === null) {
            $this->_children = [];
        }

        $this->_children[] = $folder;
    }
}
