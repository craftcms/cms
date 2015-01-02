<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

/**
 * Token record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Token extends BaseRecord
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
		return 'tokens';
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('token'), 'unique' => true),
			array('columns' => array('expiryDate')),
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
			'token'      => array(AttributeType::String, 'column' => ColumnType::Char, 'length' => 32, 'required' => true),
			'route'      => array(AttributeType::Mixed),
			'usageLimit' => array(AttributeType::Number, 'min' => 0, 'max' => 255),
			'usageCount' => array(AttributeType::Number, 'min' => 0, 'max' => 255),
			'expiryDate' => array(AttributeType::DateTime, 'required' => true),
		);
	}
}
