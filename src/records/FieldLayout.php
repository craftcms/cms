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
 * Field layout record class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayout extends ActiveRecord
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
		return '{{%fieldlayouts}}';
	}

	/**
	 * Returns the field layout’s tabs.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getTabs()
	{
		return $this->hasMany(FieldLayoutTab::className(), ['layoutId' => 'id']);
	}

	/**
	 * Returns the field layout’s fields.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getFields()
	{
		return $this->hasMany(FieldLayoutField::className(), ['layoutId' => 'id']);
	}

	/**
	 * @inheritDoc ActiveRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['type']],
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
			'type' => [AttributeType::ClassName, 'required' => true],
		];
	}
}
