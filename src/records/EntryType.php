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

/**
 * Class EntryType record.
 *
 * @var integer $id ID
 * @var integer $sectionId Section ID
 * @var integer $fieldLayoutId Field layout ID
 * @var string $name Name
 * @var string $handle Handle
 * @var boolean $hasTitleField Has title field
 * @var string $titleLabel Title label
 * @var string $titleFormat Title format
 * @var string $sortOrder Sort order
 * @var ActiveQueryInterface $section Section
 * @var ActiveQueryInterface $fieldLayout Field layout

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
