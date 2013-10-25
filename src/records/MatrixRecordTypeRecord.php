<?php
namespace Craft;

/**
 * Stores matrix record types
 */
class MatrixRecordTypeRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'matrixrecordtypes';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'       => array(AttributeType::Name, 'required' => true),
			'handle'     => array(AttributeType::Handle, 'required' => true),
			'sortOrder'  => AttributeType::SortOrder,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'field'       => array(static::BELONGS_TO, 'FieldRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'fieldLayout' => array(static::BELONGS_TO, 'FieldLayoutRecord', 'onDelete' => static::SET_NULL),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('name', 'fieldId'), 'unique' => true),
			array('columns' => array('handle', 'fieldId'), 'unique' => true),
		);
	}
}
