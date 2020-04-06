<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\Table;
use craft\validators\DateTimeValidator;
use yii\db\ActiveQueryInterface;

/**
 * Class AssetIndexData record.
 *
 * @property int $id ID
 * @property int $volumeId Volume ID
 * @property string $sessionId Session ID
 * @property string $uri URI
 * @property int $size Size
 * @property \DateTime $timestamp Timestamp
 * @property bool $inProgress In progress
 * @property bool $completed Is completed
 * @property int $recordId Record ID
 * @property Volume $volume Volume
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AssetIndexData extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['volumeId', 'recordId', 'size'], 'number', 'integerOnly' => true],
            [['timestamp'], DateTimeValidator::class],
            [['sessionId', 'volumeId'], 'required'],
            [['sessionId'], 'string', 'length' => 36],
            [['uri'], 'string'],
            [['completed', 'inProgress'], 'boolean'],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::ASSETINDEXDATA;
    }

    /**
     * Returns the asset index data’s volume.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getVolume(): ActiveQueryInterface
    {
        return $this->hasOne(Volume::class, ['id' => 'volumeId']);
    }
}
