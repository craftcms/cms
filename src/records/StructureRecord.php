<?php
namespace Craft;

/**
 *
 */
class StructureRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'structures';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'maxLevels'      => array(AttributeType::Number, 'min' => 1, 'column' => ColumnType::SmallInt),
			'movePermission' => AttributeType::String,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'elements' => array(static::HAS_MANY, 'StructureElementRecord', 'structureId'),
		);
	}
}
