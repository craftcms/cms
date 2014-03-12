<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_000010_no_section_default_author extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (craft()->db->columnExists('sections', 'defaultAuthorId'))
		{
			Craft::log('Dropping foreign key from sections to users table for default author.', LogLevel::Info, true);
			MigrationHelper::dropForeignKeyIfExists('sections', array('defaultAuthorId'));

			Craft::log('Removing defaultAuthorId column from sections table.', LogLevel::Info, true);
			$this->dropColumn('sections', 'defaultAuthorId');
		}
		else
		{
			Craft::log('defaultAuthorId does not exist in the sections, table.  All is well.', LogLevel::Info, true);
		}

		return true;
	}
}
