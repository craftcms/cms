<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;

/**
 * Class VolumeFolder record.
 *
 * @property integer      $id       ID
 * @property integer      $parentId Parent ID
 * @property integer      $volumeId Volume ID
 * @property string       $name     Name
 * @property string       $path     Path
 * @property VolumeFolder $parent   Parent
 * @property Volume       $volume   Volume
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
            [
                ['name'],
                'unique',
                'targetAttribute' => ['name', 'parentId', 'volumeId']
            ],
            [['name'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%volumefolders}}';
    }

    /**
     * Returns the asset folder’s parent.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getParent()
    {
        return $this->hasOne(VolumeFolder::className(), ['id' => 'parentId']);
    }

    /**
     * Returns the asset folder’s source.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getVolume()
    {
        return $this->hasOne(Volume::className(), ['id' => 'volumeId']);
    }
}
