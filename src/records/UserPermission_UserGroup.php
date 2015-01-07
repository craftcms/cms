<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\Craft;

Craft::$app->requireEdition(Craft::Pro);

/**
 * Class UserPermission_UserGroup record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserPermission_UserGroup extends BaseRecord
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
		return 'userpermissions_usergroups';
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
			'group'      => array(static::BELONGS_TO, 'UserGroup',      'required' => true, 'onDelete' => static::CASCADE),
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
			array('columns' => array('permissionId', 'groupId'), 'unique' => true),
		);
	}
}
