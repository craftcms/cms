<?php
namespace craft\app\records;

use craft\app\Craft;

craft()->requireEdition(Craft::Pro);

/**
 * Class UserPermission_User record.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     3.0
 */
class UserPermission_User extends BaseRecord
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
		return 'userpermissions_users';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'permission' => array(static::BELONGS_TO, 'UserPermission', 'required' => true, 'onDelete' => static::CASCADE),
			'user'       => array(static::BELONGS_TO, 'User',           'required' => true, 'onDelete' => static::CASCADE),
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
			array('columns' => array('permissionId', 'userId'), 'unique' => true),
		);
	}
}
