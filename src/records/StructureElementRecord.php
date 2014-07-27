<?php
namespace Craft;

/**
 * Class StructureElementRecord
 *
 * @package craft.app.records
 */
class StructureElementRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'structureelements';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'root'  => array(AttributeType::Number, 'column' => ColumnType::Int,      'unsigned' => true),
			'lft'   => array(AttributeType::Number, 'column' => ColumnType::Int,      'unsigned' => true, 'null' => false),
			'rgt'   => array(AttributeType::Number, 'column' => ColumnType::Int,      'unsigned' => true, 'null' => false),
			'level' => array(AttributeType::Number, 'column' => ColumnType::SmallInt, 'unsigned' => true, 'null' => false),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'structure' => array(static::BELONGS_TO, 'StructureRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'element'   => array(static::BELONGS_TO, 'ElementRecord', 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('structureId', 'elementId'), 'unique' => true),
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
}
