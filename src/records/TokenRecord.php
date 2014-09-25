<?php
namespace Craft;

/**
 * Token record.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     2.1
 */
class TokenRecord extends BaseRecord
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
