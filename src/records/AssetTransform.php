<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\db\ActiveRecord;

/**
 * Class AssetTransform record.
 *
 * @property integer $id ID
 * @property string $name Name
 * @property string $handle Handle
 * @property string $mode Mode
 * @property string $position Position
 * @property integer $height Height
 * @property integer $width Width
 * @property string $format Format
 * @property integer $quality Quality
 * @property \DateTime $dimensionChangeTime Dimension change time
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetTransform extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['handle'], 'craft\\app\\validators\\Handle', 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
			[['mode'], 'in', 'range' => ['stretch', 'fit', 'crop']],
			[['position'], 'in', 'range' => ['top-left', 'top-center', 'top-right', 'center-left', 'center-center', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right']],
			[['height'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['width'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['quality'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['dimensionChangeTime'], 'craft\\app\\validators\\DateTime'],
			[['name', 'handle'], 'unique'],
			[['name', 'handle', 'mode', 'position'], 'required'],
			[['handle'], 'string', 'max' => 255],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%assettransforms}}';
	}
}
