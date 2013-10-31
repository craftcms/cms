<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m131105_000007_new_relation_column_names extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (craft()->db->columnExists('relations', 'parentId'))
		{
			MigrationHelper::renameColumn('relations', 'parentId', 'sourceId');
		}

		if (craft()->db->columnExists('relations', 'childId'))
		{
			MigrationHelper::renameColumn('relations', 'childId', 'targetId');
		}

		return true;
	}
}
