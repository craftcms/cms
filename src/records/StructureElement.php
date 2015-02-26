<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use Craft;
use craft\app\db\StructuredElementQuery;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;
use creocoder\nestedsets\NestedSetsBehavior;

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
	 * @inheritDoc
	 */
	public static function find()
	{
		return Craft::createObject(StructuredElementQuery::className(), [get_called_class()]);
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
	 * @inheritDoc
	 */
	public function behaviors()
	{
		return [
			'tree' => [
				'class' => NestedSetsBehavior::className(),
				'treeAttribute' => 'root',
				'leftAttribute' => 'lft',
				'rightAttribute' => 'rgt',
				'depthAttribute' => 'level',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function transactions()
	{
		return [
			static::SCENARIO_DEFAULT => static::OP_ALL,
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
