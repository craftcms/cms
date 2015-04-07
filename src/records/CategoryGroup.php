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
 * Class CategoryGroup record.
 *
 * @property integer $id ID
 * @property integer $structureId Structure ID
 * @property integer $fieldLayoutId Field layout ID
 * @property string $name Name
 * @property string $handle Handle
 * @property boolean $hasUrls Has URLs
 * @property string $template Template
 * @property ActiveQueryInterface $structure Structure
 * @property ActiveQueryInterface $fieldLayout Field layout
 * @property ActiveQueryInterface $locales Locales
 * @property ActiveQueryInterface $categories Categories
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CategoryGroup extends ActiveRecord
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
			[['name', 'handle'], 'unique'],
			[['name', 'handle'], 'required'],
			[['name', 'handle'], 'string', 'max' => 255],
			[['template'], 'string', 'max' => 500],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%categorygroups}}';
	}

	/**
	 * Returns the category group’s structure.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getStructure()
	{
		return $this->hasOne(Structure::className(), ['id' => 'structureId']);
	}

	/**
	 * Returns the category group’s fieldLayout.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getFieldLayout()
	{
		return $this->hasOne(FieldLayout::className(), ['id' => 'fieldLayoutId']);
	}

	/**
	 * Returns the category group’s locales.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getLocales()
	{
		return $this->hasMany(CategoryGroupLocale::className(), ['groupId' => 'id']);
	}

	/**
	 * Returns the category group’s categories.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getCategories()
	{
		return $this->hasMany(Category::className(), ['groupId' => 'id']);
	}
}
