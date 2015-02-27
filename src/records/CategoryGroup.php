<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;
use craft\app\enums\AttributeType;

/**
 * Class CategoryGroup record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CategoryGroup extends BaseRecord
{
	// Public Methods
	// =========================================================================

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

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['name'], 'unique' => true],
			['columns' => ['handle'], 'unique' => true],
		];
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'name'      => [AttributeType::Name, 'required' => true],
			'handle'    => [AttributeType::Handle, 'required' => true],
			'hasUrls'   => [AttributeType::Bool, 'default' => true],
			'template'  => AttributeType::Template,
		];
	}
}
