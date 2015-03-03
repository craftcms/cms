<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\db\ActiveRecord;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

/**
 * Class Structure record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Structure extends ActiveRecord
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
		return '{{%structures}}';
	}

	/**
	 * Returns the structure’s elements.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getElements()
	{
		return $this->hasMany(StructureElement::className(), ['structureId' => 'id']);
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
			'maxLevels' => [AttributeType::Number, 'min' => 1, 'column' => ColumnType::SmallInt],
		];
	}
}
