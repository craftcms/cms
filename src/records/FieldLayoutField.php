<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

/**
 * Class FieldLayoutField record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayoutField extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::getTableName()
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'fieldlayoutfields';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'layout' => array(static::BELONGS_TO, 'FieldLayout', 'required' => true, 'onDelete' => static::CASCADE),
			'tab'    => array(static::BELONGS_TO, 'FieldLayoutTab', 'required' => true, 'onDelete' => static::CASCADE),
			'field'  => array(static::BELONGS_TO, 'Field', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
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
	 * @inheritDoc BaseRecord::defineAttributes()
	 *
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
