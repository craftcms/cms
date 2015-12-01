<?php
namespace Craft;

/**
 * Class TaskRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.records
 * @since     2.0
 */
class TaskRecord extends BaseRecord
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
		return array(
			'root'          => array(AttributeType::Number,    'column' => ColumnType::Int,      'unsigned' => true),
			'lft'           => array(AttributeType::Number,    'column' => ColumnType::Int,      'unsigned' => true, 'null' => false),
			'rgt'           => array(AttributeType::Number,    'column' => ColumnType::Int,      'unsigned' => true, 'null' => false),
			'level'         => array(AttributeType::Number,    'column' => ColumnType::SmallInt, 'unsigned' => true, 'null' => false),
			'currentStep'   => array(AttributeType::Number,    'column' => ColumnType::Int,      'unsigned' => true),
			'totalSteps'    => array(AttributeType::Number,    'column' => ColumnType::Int,      'unsigned' => true),
			'status'        => array(AttributeType::Enum,      'values' => array(TaskStatus::Pending, TaskStatus::Error, TaskStatus::Running)),
			'type'          => array(AttributeType::ClassName, 'required' => true),
			'description'   => AttributeType::String,
			'settings'      => AttributeType::Mixed,
		);
	}
}
