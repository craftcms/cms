<?php
namespace Craft;

/**
 * Class StructureRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     2.0
 */
class StructureRecord extends BaseRecord
{
	////////////////////
	// PUBLIC METHODS
	////////////////////

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'structures';
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

	////////////////////
	// PROTECTED METHODS
	////////////////////

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'maxLevels'      => array(AttributeType::Number, 'min' => 1, 'column' => ColumnType::SmallInt),
			'movePermission' => AttributeType::String,
		);
	}
}
