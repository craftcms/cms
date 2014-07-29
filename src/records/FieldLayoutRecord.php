<?php
namespace Craft;

/**
 * Field layout record class
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.0
 */
class FieldLayoutRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'fieldlayouts';
	}

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'type' => array(AttributeType::ClassName, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'tabs'   => array(static::HAS_MANY, 'FieldLayoutTabRecord', 'layoutId', 'order' => 'tabs.sortOrder'),
			'fields' => array(static::HAS_MANY, 'FieldLayoutFieldRecord', 'layoutId', 'order' => 'fields.sortOrder'),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('type')),
		);
	}
}
