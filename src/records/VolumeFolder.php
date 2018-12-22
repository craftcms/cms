<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Class VolumeFolder record.
 *
 * @property int $id ID
 * @property int $parentId Parent ID
 * @property int $volumeId Volume ID
 * @property string $name Name
 * @property string $path Path
 * @property VolumeFolder $parent Parent
 * @property Volume $volume Volume
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class VolumeFolder extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'unique', 'targetAttribute' => ['name', 'parentId', 'volumeId']],
            [['name'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%volumefolders}}';
    }

    /**
     * Returns the asset folder’s parent.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getParent(): ActiveQueryInterface
    {
        return $this->hasOne(__CLASS__, ['id' => 'parentId']);
    }

    /**
     * Returns the asset folder’s source.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getVolume(): ActiveQueryInterface
    {
        return $this->hasOne(Volume::class, ['id' => 'volumeId']);
    }
}
