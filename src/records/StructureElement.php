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
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return [
			'structure' => [static::BELONGS_TO, 'Structure', 'required' => true, 'onDelete' => static::CASCADE],
			'element'   => [static::BELONGS_TO, 'Element', 'onDelete' => static::CASCADE],
		];
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
