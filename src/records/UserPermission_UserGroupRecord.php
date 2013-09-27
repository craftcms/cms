<?php
namespace Craft;

craft()->requirePackage(CraftPackage::Users);

/**
 *
 */
class UserPermission_UserGroupRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'userpermissions_usergroups';
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'permission' => array(static::BELONGS_TO, 'UserPermissionRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'group'      => array(static::BELONGS_TO, 'UserGroupRecord',      'required' => true, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('permissionId', 'groupId'), 'unique' => true),
		);
	}
}
