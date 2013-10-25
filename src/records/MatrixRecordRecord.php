<?php
namespace Craft;

/**
 * Stores Matrix records
 */
class MatrixRecordRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'matrixrecords';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'sortOrder' => AttributeType::SortOrder,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element' => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'owner'   => array(static::BELONGS_TO, 'ElementRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'field'   => array(static::BELONGS_TO, 'FieldRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'type'    => array(static::BELONGS_TO, 'MatrixRecordTypeRecord', 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('ownerId')),
			array('columns' => array('fieldId')),
			array('columns' => array('typeId')),
			array('columns' => array('sortOrder')),
		);
	}
}
