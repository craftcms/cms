<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130815_000000_add_cascade_delete_to_relations_again extends BaseMigration
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
			MigrationHelper::dropForeignKeyIfExists('relations', array('childId'));

			Craft::log('Dropping foreign key for the `parentId` column on the `relations` table.', LogLevel::Info, true);
			MigrationHelper::dropForeignKeyIfExists('relations', array('parentId'));

			Craft::log('Dropping foreign key for the `fieldId` column on the `relations` table.', LogLevel::Info, true);
			MigrationHelper::dropForeignKeyIfExists('relations', array('fieldId'));

			// Make sure there are no orphans before we re-add the FK's.
			$this->_murderOrphans();

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

	/**
	 *
	 */
	private function _murderOrphans()
	{
		// Grab all the element ids.
		$elementIds = craft()->db->createCommand()
							->select('id')
							->from('elements')
							->queryColumn();

		$elementIds = array_filter($elementIds);

		// Grab any orphaned rows in the relations table so we can log the crime we're about to commit.
		$orphans = craft()->db->createCommand()
			->select('id,childId,parentId')
			->from('relations')
			->where(array('or',
						array('not in', 'parentId', $elementIds),
						array('not in', 'childId', $elementIds)
					))
			->queryAll();

		$orphans = array_filter($orphans);

		// Log
		if ($orphans && count($orphans) > 0)
		{
			Craft::log('Found '.count($orphans).' orphaned relations in the `relations` table.', LogLevel::Info, true);
			foreach ($orphans as $orphan)
			{
				Craft::log('Orphaned relation - id: '.$orphan['id'].' childId: '.$orphan['childId'].' parentId: '.$orphan['parentId'].'', LogLevel::Info, true);
			}

			Craft::log('Murdering orphans...', LogLevel::Info, true);

			$this->delete('relations', array('or',
				array('not in', 'parentId', $elementIds),
				array('not in', 'childId', $elementIds)
			));

			Craft::log('I did it.  They are all dead.', LogLevel::Info, true);
		}
		else
		{
			Craft::log('No orphans to murder today.', LogLevel::Info, true);
		}
	}
}
