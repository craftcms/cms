<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130805_193360_add_cascade_delete_to_relations_again extends BaseMigration
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
			Craft::log('Dropping foreign key for the `childId` column on the `relations` table.', LogLevel::Info, true);
			$this->dropForeignKey('relations', 'childId');

			Craft::log('Dropping foreign key for the `parentId` column on the `relations` table.', LogLevel::Info, true);
			$this->dropForeignKey('relations', 'parentId');

			Craft::log('Dropping foreign key for the `fieldId` column on the `relations` table.', LogLevel::Info, true);
			$this->dropForeignKey('relations', 'fieldId');

			Craft::log('Adding foreign key for the `childId` column on the `relations` table.', LogLevel::Info, true);
			$this->addForeignKey('relations', 'childId', 'elements', 'id', 'CASCADE');

			Craft::log('Adding foreign key for the `parentId` column on the `relations` table.', LogLevel::Info, true);
			$this->addForeignKey('relations', 'parentId', 'elements', 'id', 'CASCADE');

			Craft::log('Adding foreign key for the `fieldId` column on the `relations` table.', LogLevel::Info, true);
			$this->addForeignKey('relations', 'fieldId', 'fields', 'id', 'CASCADE');
		}
		else
		{
			Craft::log('Could not find an `relations` table. Wut?', LogLevel::Error);
		}
	}
}
