<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130908_000000_entry_draft_and_revision_deletes extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Entry Versions
		Craft::log('Dropping foreign key for the `creatorId` column on the `entryversions` table.', LogLevel::Info, true);
		MigrationHelper::dropForeignKeyIfExists('entryversions', array('creatorId'));

		Craft::log('Making `creatorId` nullable on the table `entryversions`.', LogLevel::Info, true);
		$this->alterColumn('entryversions', 'creatorId', array('column' => ColumnType::Int, 'null' => true));

		Craft::log('Adding foreign key for the `creatorId` column on the `entryversions` table with a delete set to nullable.', LogLevel::Info, true);
		$this->addForeignKey('entryversions', 'creatorId', 'users', 'id', 'SET NULL');

		// Entry Drafts
		Craft::log('Dropping foreign key for the `creatorId` column on the `entrydrafts` table.', LogLevel::Info, true);
		MigrationHelper::dropForeignKeyIfExists('entrydrafts', array('creatorId'));

		Craft::log('Adding foreign key for the `creatorId` column on the `entrydrafts` table with a cascade delete set', LogLevel::Info, true);
		$this->addForeignKey('entrydrafts', 'creatorId', 'users', 'id', 'CASCADE');

		return true;
	}
}
