<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\enums\AttributeType;

/**
 * Class FieldLayoutField record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayoutField extends BaseRecord
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
		return '{{%fieldlayoutfields}}';
	}

	/**
	 * Returns the field layout field’s layout.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getLayout()
	{
		return $this->hasOne(FieldLayout::className(), ['id' => 'layoutId']);
	}

	/**
	 * Returns the field layout field’s tab.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getTab()
	{
		return $this->hasOne(FieldLayoutTab::className(), ['id' => 'tabId']);
	}

	/**
	 * Returns the field layout field’s field.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getField()
	{
		return $this->hasOne(Field::className(), ['id' => 'fieldId']);
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['layoutId', 'fieldId'], 'unique' => true],
			['columns' => ['sortOrder']],
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
			'required'  => AttributeType::Bool,
			'sortOrder' => AttributeType::SortOrder,
		];
	}
}
