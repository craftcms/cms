<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\helpers\Html;
use yii\base\InvalidConfigException;

/**
 * The VolumeFolder model class.
 *
 * @property-read Volume $volume
 * @property-read VolumeFolder|null $parent
 * @property VolumeFolder[] $children
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class VolumeFolder extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var int|string|null Parent ID
     */
    public string|int|null $parentId = null;

    /**
     * @var int|null Volume ID
     */
    public ?int $volumeId = null;

    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null Path
     */
    public ?string $path = null;

    /**
     * @var string|null UID
     */
    public ?string $uid = null;

    /**
     * @var VolumeFolder[]|null
     */
    private ?array $_children = null;

    /**
     * @var bool
     */
    private bool $_hasChildren;

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
     * @return Volume
     * @throws InvalidConfigException if [[volumeId]] is invalid
     */
    public function getVolume(): Volume
    {
        if (!isset($this->volumeId)) {
            return Craft::$app->getVolumes()->getTemporaryVolume();
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
     * @since 4.4.0
     */
    public function getSourcePathInfo(): ?array
    {
        if (!$this->volumeId) {
            return null;
        }

        $volume = $this->getVolume();
        $userSession = Craft::$app->getUser();
        $canView = $userSession->checkPermission("viewAssets:$volume->uid");
        $canCreate = $userSession->checkPermission("createFolders:$volume->uid");
        $canDelete = $userSession->checkPermission("deletePeerAssets:$volume->uid");
        $canMove = $canDelete && $userSession->checkPermission("savePeerAssets:$volume->uid");

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
                'icon' => 'home',
                'label' => Craft::t('app', '{volume} root', [
                    'volume' => Html::encode(Craft::t('site', $volume->name)),
                ]),
                'handle' => $volume->handle,
            ];
        } else {
            $canRename = $canCreate & $userSession->checkPermission("deleteAssets:$volume->uid");

            $info += [
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
     * @since 4.4.0
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
     * @since 4.4.0
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
    public function setChildren(array $children): void
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
        if (isset($this->_children)) {
            return $this->_children;
        }

        return $this->_children = Craft::$app->getAssets()->findFolders(['parentId' => $this->id]);
    }

    /**
     * @return VolumeFolder|null
     */
    public function getParent(): ?VolumeFolder
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
    public function addChild(VolumeFolder $folder): void
    {
        if (!isset($this->_children)) {
            $this->_children = [];
        }

        $this->_children[] = $folder;
    }
}
