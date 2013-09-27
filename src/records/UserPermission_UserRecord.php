<?php
namespace Craft;

craft()->requirePackage(CraftPackage::Users);

/**
 *
 */
class UserPermission_UserRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'userpermissions_users';
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'permission' => array(static::BELONGS_TO, 'UserPermissionRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'user'       => array(static::BELONGS_TO, 'UserRecord',           'required' => true, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('permissionId', 'userId'), 'unique' => true),
		);
	}
}
