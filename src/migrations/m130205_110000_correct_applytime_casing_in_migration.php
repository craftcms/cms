<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130205_110000_correct_applytime_casing_in_migration extends DbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$migrationsTable = blx()->db->schema->getTable('{{migrations}}');

		if ($migrationsTable)
		{
			if (!$migrationsTable->getColumn('applyTime') && $migrationsTable->getColumn('apply_time'))
			{
				blx()->db->createCommand()->renameColumn('{{migrations}}', 'apply_time', 'applyTime');

				blx()->db->getSchema()->refresh();
				MigrationRecord::model()->refreshMetaData();
			}
			else
			{
				Blocks::log('The `applyTime` column already exists in the `migrations` table.', \CLogger::LEVEL_WARNING);
			}
		}
		else
		{
			Blocks::log('The `migrations` table is missing. No idea what is going on here.', \CLogger::LEVEL_ERROR);
		}
	}
}
