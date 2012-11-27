<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m121126_225547_cascade_deletes extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Find all record classes
		$records = blx()->install->findInstallableRecords();

		// Add any section content records to the mix
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$criteria = new SectionCriteria();
			$criteria->limit = null;
			$sections = blx()->sections->findSections($criteria);

			foreach ($sections as $section)
			{
				$records[] = new SectionContentRecord($section);
			}
		}

		// Drop the foreign keys
		foreach ($records as $record)
		{
			$record->dropForeignKeys();
		}

		// Add them back (this time with CASCADE rules on FK deletion)
		foreach ($records as $record)
		{
			$record->addForeignKeys();
		}

		// Don't forget the usergroups_users table
		blx()->db->createCommand()->dropForeignKey('usergroups_users_group_fk', 'usergroups_users');
		blx()->db->createCommand()->dropForeignKey('usergroups_users_user_fk', 'usergroups_users');
		blx()->db->createCommand()->addForeignKey('usergroups_users_group_fk', 'usergroups_users', 'groupId', 'usergroups', 'id', 'CASCADE');
		blx()->db->createCommand()->addForeignKey('usergroups_users_user_fk', 'usergroups_users', 'userId', 'users', 'id', 'CASCADE');

		// Drop the old pluginId FK from blx_widgets while we're at it
		//blx()->db->createCommand()->dropForeignKey('widgets_plugin_fk', 'widgets');
		//blx()->db->createCommand()->dropColumn('widgets', 'pluginId');

		return true;
	}
}
