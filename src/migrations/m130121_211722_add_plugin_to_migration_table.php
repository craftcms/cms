<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130121_211722_add_plugin_to_migration_table extends DbMigration
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
			if (!$migrationsTable->getColumn('pluginId'))
			{
				blx()->db->createCommand()->addColumnAfter('migrations', 'pluginId', array(ColumnType::Int), 'id');
				blx()->db->createCommand()->addForeignKey('migrations', 'pluginId', 'plugins', 'id', BaseRecord::CASCADE);
			}
			else
			{
				Blocks::log('The `pluginId` column already exists in the `migrations` table.', \CLogger::LEVEL_WARNING);
			}
		}
		else
		{
			Blocks::log('The `migrations` table has not been created yet.', \CLogger::LEVEL_WARNING);
		}
	}
}
