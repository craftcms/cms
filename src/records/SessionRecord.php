<?php
namespace Craft;

/**
 * Class SessionRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.0
 */
class SessionRecord extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'sessions';
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'user' => array(static::BELONGS_TO, 'UserRecord', 'onDelete' => static::CASCADE, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('uid')),
			array('columns' => array('token')),
			array('columns' => array('dateUpdated')),
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
			'token' => array(AttributeType::String, 'maxLength' => 100, 'column' => ColumnType::Char, 'required' => true),
		);
	}
}
