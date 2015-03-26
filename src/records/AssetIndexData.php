<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

/**
 * Class AssetIndexData record.
 *
 * @var integer $id ID
 * @var integer $volumeId Volume ID
 * @var string $sessionId Session ID
 * @var integer $offset Offset
 * @var string $uri URI
 * @var integer $size Size
 * @var \DateTime $timestamp Timestamp
 * @var integer $recordId Record ID
 * @var ActiveQueryInterface $source Source

 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
			[['volumeId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['offset'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['size'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['timestamp'], 'craft\\app\\validators\\DateTime'],
			[['recordId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['sessionId'], 'unique', 'targetAttribute' => ['sessionId', 'volumeId', 'offset']],
			[['sessionId', 'volumeId', 'offset'], 'required'],
			[['sessionId'], 'string', 'length' => 36],
			[['uri'], 'string', 'max' => 255],
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
	 * Returns the asset index data’s source.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getSource()
	{
		return $this->hasOne(Volume::className(), ['id' => 'volumeId']);
	}
}
