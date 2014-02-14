<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140204_000013_assignUserPermissions_permission extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Make sure an "assignUserPermissions" permission doesn't already exist
		$permissionExists = (bool) craft()->db->createCommand()
			->from('userpermissions')
			->where('name = "assignuserpermissions"')
			->count('id');

		if (!$permissionExists)
		{
			// Find all of the users that have the "administrateUsers" permission
			$userIds = craft()->db->createCommand()
				->select('up_u.userId')
				->from('userpermissions_users up_u')
				->join('userpermissions up', 'up.id = up_u.permissionId')
				->where('up.name = "administrateusers"')
				->queryColumn();

			// Find all of the user groups that have the "administrateUsers" permission
			$groupIds = craft()->db->createCommand()
				->select('up_g.groupId')
				->from('userpermissions_usergroups up_g')
				->join('userpermissions up', 'up.id = up_g.permissionId')
				->where('up.name = "administrateusers"')
				->queryColumn();

			if ($userIds || $groupIds)
			{
				// Add the new permission row and get its ID
				$this->insert('userpermissions', array('name' => 'assignuserpermissions'));
				$permissionId = craft()->db->getLastInsertID();

				// Assign any users with administrateUsers permission to the new one
				if ($userIds)
				{
					$values = array();

					foreach ($userIds as $userId)
					{
						$values[] = array($permissionId, $userId);
					}

					$this->insertAll('userpermissions_users', array('permissionId', 'userId'), $values);
				}

				// Assign any user groups with administrateUsers permission to the new one
				if ($groupIds)
				{
					$values = array();

					foreach ($groupIds as $groupId)
					{
						$values[] = array($permissionId, $groupId);
					}

					$this->insertAll('userpermissions_usergroups', array('permissionId', 'groupId'), $values);
				}
			}
		}

		return true;
	}
}
