<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m171107_000000_assign_group_permissions extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// See which users & groups already have the "assignUserPermissions" permission
		$userIds = $this->getDbConnection()->createCommand()
			->select('up_u.userId')
			->from('userpermissions_users up_u')
			->join('userpermissions up', 'up.id = up_u.permissionId')
			->where('up.name = "assignuserpermissions"')
			->queryColumn();

		$groupIds = $this->getDbConnection()->createCommand()
			->select('up_ug.groupId')
			->from('userpermissions_usergroups up_ug')
			->join('userpermissions up', 'up.id = up_ug.permissionId')
			->where('up.name = "assignuserpermissions"')
			->queryColumn();

		if (empty($userIds) && empty($groupIds))
		{
			return true;
		}

		// Get the user group IDs
		$allGroupIds = $this->getDbConnection()->createCommand()
			->select('id')
			->from('usergroups')
			->queryColumn();

		// Create the new permissions
		$permissionIds = array();

		$this->insert('userpermissions', array('name' => 'assignusergroups'));
		$permissionIds[] = $this->getDbConnection()->getLastInsertID();

		foreach ($allGroupIds as $groupId)
		{
			$this->insert('userpermissions', array('name' => 'assignusergroup:'.$groupId));
			$permissionIds[] = $this->getDbConnection()->getLastInsertID();
		}

		// Assign the new permissions to the users
		if (!empty($userIds))
		{
			$data = array();
			foreach ($userIds as $userId)
			{
				foreach ($permissionIds as $permissionId)
				{
					$data[] = array($permissionId, $userId);
				}
			}
			$this->insertAll('userpermissions_users', array('permissionId', 'userId'), $data);
		}

		// Assign the new permissions to the groups
		if (!empty($groupIds))
		{
			$data = array();
			foreach ($groupIds as $groupId)
			{
				foreach ($permissionIds as $permissionId)
				{
					$data[] = array($permissionId, $groupId);
				}
			}
			$this->insertAll('userpermissions_usergroups', array('permissionId', 'groupId'), $data);
		}

		return true;
	}
}
