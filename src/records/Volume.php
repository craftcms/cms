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
 * Class Volume record.
 *
 * @property integer $id ID
 * @property integer $fieldLayoutId Field layout ID
 * @property string $name Name
 * @property string $handle Handle
 * @property string $type Type
 * @property string $url URL
 * @property array $settings Settings
 * @property string $sortOrder Sort order
 * @property ActiveQueryInterface $fieldLayout Field layout
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Volume extends ActiveRecord
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
			[['fieldLayoutId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['name', 'handle'], 'unique'],
			[['name', 'handle', 'type', 'url'], 'required'],
			[['name', 'handle'], 'string', 'max' => 255],
			[['type'], 'string', 'max' => 150],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%volumes}}';
	}

	/**
	 * Returns the asset source’s fieldLayout.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getFieldLayout()
	{
		return $this->hasOne(FieldLayout::className(), ['id' => 'fieldLayoutId']);
	}
}
