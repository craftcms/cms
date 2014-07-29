<?php
namespace Craft;

/**
 * Class StructureRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.0
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
