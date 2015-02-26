<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use Craft;
use craft\app\db\TaskQuery;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;
use craft\app\enums\TaskStatus;
use creocoder\nestedsets\NestedSetsBehavior;

/**
 * Class Task record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Task extends BaseRecord
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
		return '{{%tasks}}';
	}

	/**
	 * @inheritDoc
	 */
	public static function find()
	{
		return Craft::createObject(TaskQuery::className(), [get_called_class()]);
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
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
			'root'          => [AttributeType::Number,    'column' => ColumnType::Int,      'unsigned' => true],
			'lft'           => [AttributeType::Number,    'column' => ColumnType::Int,      'unsigned' => true, 'null' => false],
			'rgt'           => [AttributeType::Number,    'column' => ColumnType::Int,      'unsigned' => true, 'null' => false],
			'level'         => [AttributeType::Number,    'column' => ColumnType::SmallInt, 'unsigned' => true, 'null' => false],
			'currentStep'   => [AttributeType::Number,    'column' => ColumnType::Int,      'unsigned' => true],
			'totalSteps'    => [AttributeType::Number,    'column' => ColumnType::Int,      'unsigned' => true],
			'status'        => [AttributeType::Enum,      'values' => [TaskStatus::Pending, TaskStatus::Error, TaskStatus::Running]],
			'type'          => [AttributeType::ClassName, 'required' => true],
			'description'   => AttributeType::String,
			'settings'      => AttributeType::Mixed,
		];
	}
}
