<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;

/**
 * Class AssetIndexData record.
 *
 * @property integer $id ID
 * @property integer $volumeId Volume ID
 * @property string $sessionId Session ID
 * @property integer $offset Offset
 * @property string $uri URI
 * @property integer $size Size
 * @property \DateTime $timestamp Timestamp
 * @property integer $recordId Record ID
 * @property ActiveQueryInterface $source Source
 *
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
