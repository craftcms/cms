<?php
namespace Craft;

/**
 * Class TaskRecord
 *
 * @package craft.app.records
 */
class TaskRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'tasks';
	}

	/**
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

	/**
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
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'nestedSet' => 'app.extensions.NestedSetBehavior',
		);
	}

	/**
	 * @return array
	 */
	public function scopes()
	{
		return array(
			'ordered' => array('order' => 'dateCreated'),
		);
	}
}
