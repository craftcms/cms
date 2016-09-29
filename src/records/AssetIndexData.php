<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;
use craft\app\validators\DateTimeValidator;

/**
 * Class AssetIndexData record.
 *
 * @property integer   $id        ID
 * @property integer   $volumeId  Volume ID
 * @property string    $sessionId Session ID
 * @property integer   $offset    Offset
 * @property string    $uri       URI
 * @property integer   $size      Size
 * @property \DateTime $timestamp Timestamp
 * @property integer   $recordId  Record ID
 * @property Volume    $volume    Volume
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AssetIndexData extends ActiveRecord
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
                ['volumeId'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['offset'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['size'],
                'number',
                'min' => 0,
                'max' => 18446744073709551615,
                'integerOnly' => true
            ],
            [['timestamp'], DateTimeValidator::class],
            [
                ['recordId'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['sessionId'],
                'unique',
                'targetAttribute' => ['sessionId', 'volumeId', 'offset']
            ],
            [['sessionId', 'volumeId', 'offset'], 'required'],
            [['sessionId'], 'string', 'length' => 36],
            [['uri'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%assetindexdata}}';
    }

    /**
     * Returns the asset index data’s volume.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getVolume()
    {
        return $this->hasOne(Volume::class, ['id' => 'volumeId']);
    }
}
