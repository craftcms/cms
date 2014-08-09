<?php
namespace Craft;

/**
 * Class FieldLayoutFieldRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.0
 */
class FieldLayoutFieldRecord extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'fieldlayoutfields';
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'layout' => array(static::BELONGS_TO, 'FieldLayoutRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'tab'    => array(static::BELONGS_TO, 'FieldLayoutTabRecord', 'onDelete' => static::CASCADE),
			'field'  => array(static::BELONGS_TO, 'FieldRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('layoutId', 'fieldId'), 'unique' => true),
			array('columns' => array('sortOrder')),
		);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'required'  => AttributeType::Bool,
			'sortOrder' => AttributeType::SortOrder,
		);
	}
}
