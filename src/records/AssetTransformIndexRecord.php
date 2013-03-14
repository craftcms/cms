<?php
namespace Craft;

/**
 *
 */
class AssetTransformIndexRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'assettransformindex';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'fileId'       => array('maxLength' => 11, 'column' => ColumnType::Int, 'required' => true),
			'location'     => array('maxLength' => 255, 'column' => ColumnType::Varchar, 'required' => true),
			'sourceId'     => array('maxLength' => 11, 'column' => ColumnType::Int, 'required' => true),
			'fileExists'   => ColumnType::Bool,
			'inProgress'   => ColumnType::Bool,
			'dateIndexed'  => AttributeType::DateTime,
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('sourceId', 'fileId', 'location')),
		);
	}
}
