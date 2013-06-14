<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130613_000000_add_cascade_delete_to_relations extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$relationsTable = $this->dbConnection->schema->getTable('{{relations}}');

		if ($relationsTable)
		{
			Craft::log('Dropping foreign key for the `id` column on the `relations` table.', LogLevel::Info, true);
			$this->dropForeignKey('entries', 'id');

			Craft::log('Dropping foreign key for the `authorId` column on the `relations` table.', LogLevel::Info, true);
			$this->dropForeignKey('entries', 'authorId');

			Craft::log('Dropping foreign key for the `sectionId` column on the `relations` table.', LogLevel::Info, true);
			$this->dropForeignKey('entries', 'sectionId');

			Craft::log('Adding foreign key for the `id` column on the `relations` table.', LogLevel::Info, true);
			$this->addForeignKey('entries', 'id', 'elements', 'id', 'CASCADE');

			Craft::log('Adding foreign key for the `authorId` column on the `relations` table.', LogLevel::Info, true);
			$this->addForeignKey('entries', 'authorId', 'users', 'id', 'CASCADE');

			Craft::log('Adding foreign key for the `sectionId` column on the `relations` table.', LogLevel::Info, true);
			$this->addForeignKey('entries', 'sectionId', 'sections', 'id', 'CASCADE');
		}
		else
		{
			Craft::log('Could not find an `relations` table. Wut?', LogLevel::Error);
		}
	}
}
