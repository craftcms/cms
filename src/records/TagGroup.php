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
 * Class TagGroup record.
 *
 * @property integer $id ID
 * @property integer $fieldLayoutId Field layout ID
 * @property string $name Name
 * @property string $handle Handle
 * @property ActiveQueryInterface $fieldLayout Field layout
 * @property ActiveQueryInterface $tags Tags
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class TagGroup extends ActiveRecord
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
			[['name', 'handle'], 'required'],
			[['name', 'handle'], 'string', 'max' => 255],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%taggroups}}';
	}

	/**
	 * Returns the tag group’s fieldLayout.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getFieldLayout()
	{
		return $this->hasOne(FieldLayout::className(), ['id' => 'fieldLayoutId']);
	}

	/**
	 * Returns the tag group’s tags.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getTags()
	{
		return $this->hasMany(Tag::className(), ['groupId' => 'id']);
	}
}
