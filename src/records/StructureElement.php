<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

/**
 * Class StructureElement record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class StructureElement extends BaseRecord
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
		return '{{%structureelements}}';
	}

	/**
	 * Returns the structure element’s structure.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getStructure()
	{
		return $this->hasOne(Structure::className(), ['id' => 'structureId']);
	}

	/**
	 * Returns the structure element’s element.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getElement()
	{
		return $this->hasOne(Element::className(), ['id' => 'elementId']);
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['structureId', 'elementId'], 'unique' => true],
			['columns' => ['root']],
			['columns' => ['lft']],
			['columns' => ['rgt']],
			['columns' => ['level']],
		];
	}

	/**
	 * @inheritDoc BaseRecord::behaviors()
	 *
	 * @return array
	 */
	public function behaviors()
	{
		return [
			'nestedSet' => 'app.extensions.NestedSetBehavior',
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
			'root'  => [AttributeType::Number, 'column' => ColumnType::Int,      'unsigned' => true],
			'lft'   => [AttributeType::Number, 'column' => ColumnType::Int,      'unsigned' => true, 'null' => false],
			'rgt'   => [AttributeType::Number, 'column' => ColumnType::Int,      'unsigned' => true, 'null' => false],
			'level' => [AttributeType::Number, 'column' => ColumnType::SmallInt, 'unsigned' => true, 'null' => false],
		];
	}
}
