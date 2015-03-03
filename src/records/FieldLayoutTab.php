<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\db\ActiveRecord;
use craft\app\enums\AttributeType;

/**
 * Field record class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayoutTab extends ActiveRecord
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
		return '{{%fieldlayouttabs}}';
	}

	/**
	 * Returns the field layout tab’s layout.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getLayout()
	{
		return $this->hasOne(FieldLayout::className(), ['id' => 'layoutId']);
	}

	/**
	 * Returns the field layout tab’s fields.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getFields()
	{
		return $this->hasMany(FieldLayoutField::className(), ['tabId' => 'id']);
	}

	/**
	 * @inheritDoc ActiveRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['sortOrder']],
		];
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc ActiveRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'name'      => [AttributeType::Name, 'required' => true],
			'sortOrder' => AttributeType::SortOrder,
		];
	}
}
