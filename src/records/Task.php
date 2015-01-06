<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;
use craft\app\enums\TaskStatus;

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
	 * @inheritDoc BaseRecord::getTableName()
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'tasks';
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('root')),
			array('columns' => array('lft')),
			array('columns' => array('rgt')),
			array('columns' => array('level')),
		);
	}

	/**
	 * @inheritDoc BaseRecord::behaviors()
	 *
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'nestedSet' => 'app.extensions.NestedSetBehavior',
		);
	}

	/**
	 * @inheritDoc BaseRecord::scopes()
	 *
	 * @return array
	 */
	public function scopes()
	{
		return array(
			'ordered' => array('order' => 'dateCreated'),
		);
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
