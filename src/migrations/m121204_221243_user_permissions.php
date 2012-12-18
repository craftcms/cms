<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m121204_221243_user_permissions extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			$newRecordClasses = array('UserPermissionRecord', 'UserPermission_UserGroupRecord', 'UserPermission_UserRecord');

			foreach ($newRecordClasses as $class)
			{
				$class = __NAMESPACE__.'\\'.$class;
				$record = new $class('install');
				$record->createTable();
				$records[] = $record;
			}

			foreach ($records as $record)
			{
				$record->addForeignKeys();
			}

			// While we're here, might as well add that new unique constraint on usergroups_users
			blx()->db->createCommand()->createIndex('usergroups_users_groupId_userId_unique_idx', 'usergroups_users', 'groupId,userId', true);
		}

		return true;
	}
}
