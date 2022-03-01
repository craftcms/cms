<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
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
