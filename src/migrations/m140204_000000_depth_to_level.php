<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140204_000000_depth_to_level extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (craft()->db->columnExists('entries', 'depth'))
		{
			Craft::log('Renaming entries.depth to level.', LogLevel::Info, true);
			MigrationHelper::renameColumn('entries', 'depth', 'level');
		}

		if (craft()->db->columnExists('sections', 'maxDepth'))
		{
			Craft::log('Renaming sections.maxDepth to maxLevels.', LogLevel::Info, true);
			MigrationHelper::renameColumn('sections', 'maxDepth', 'maxLevels');
		}

		return true;
	}
}
