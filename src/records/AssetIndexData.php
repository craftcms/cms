<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\validators\DateTimeValidator;
use yii\db\ActiveQueryInterface;

/**
 * Class AssetIndexData record.
 *
 * @property int       $id        ID
 * @property int       $volumeId  Volume ID
 * @property string    $sessionId Session ID
 * @property int       $offset    Offset
 * @property string    $uri       URI
 * @property int       $size      Size
 * @property \DateTime $timestamp Timestamp
 * @property int       $recordId  Record ID
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
            [['volumeId', 'recordId', 'offset', 'size'], 'number', 'integerOnly' => true],
            [['timestamp'], DateTimeValidator::class],
            [['sessionId'], 'unique', 'targetAttribute' => ['sessionId', 'volumeId', 'offset']],
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
