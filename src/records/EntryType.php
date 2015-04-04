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
 * Class EntryType record.
 *
 * @property integer $id ID
 * @property integer $sectionId Section ID
 * @property integer $fieldLayoutId Field layout ID
 * @property string $name Name
 * @property string $handle Handle
 * @property boolean $hasTitleField Has title field
 * @property string $titleLabel Title label
 * @property string $titleFormat Title format
 * @property string $sortOrder Sort order
 * @property ActiveQueryInterface $section Section
 * @property ActiveQueryInterface $fieldLayout Field layout
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryType extends ActiveRecord
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
			[['name'], 'unique', 'targetAttribute' => ['name', 'sectionId']],
			[['handle'], 'unique', 'targetAttribute' => ['handle', 'sectionId']],
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
		return '{{%entrytypes}}';
	}

	/**
	 * Returns the entry type’s section.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getSection()
	{
		return $this->hasOne(Section::className(), ['id' => 'sectionId']);
	}

	/**
	 * Returns the entry type’s fieldLayout.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getFieldLayout()
	{
		return $this->hasOne(FieldLayout::className(), ['id' => 'fieldLayoutId']);
	}
}
