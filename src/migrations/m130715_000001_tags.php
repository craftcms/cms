<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130715_000001_tags extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		MigrationHelper::renameTable('entrytags', 'tags');
		MigrationHelper::makeElemental('tags', 'Tag');

		// Make some tweaks on the tags table
		$this->alterColumn('tags', 'name', array('column' => ColumnType::Varchar));
		$this->dropColumn('tags', 'count');

		// Create a new field group
		$this->insert('fieldgroups', array(
			'name' => 'Tags (Auto-created)'
		));

		$groupId = craft()->db->getLastInsertID();

		// Create a new Tags field

		// Find a unique handle
		for ($i = 0; true; $i++)
		{
			$handle = 'tags'.($i != 0 ? "-{$i}" : '');

			$totalFields = craft()->db->createCommand()
				->from('fields')
				->where(array('handle' => $handle))
				->count('id');

			if ($totalFields == 0)
			{
				break;
			}
		}

		$this->insert('fields', array(
			'groupId' => $groupId,
			'name'    => 'Tags',
			'handle'  => $handle,
			'type'    => 'Tags'
		));

		$fieldId = craft()->db->getLastInsertID();

		// Migrate entrytags_enrtries data into relations
		$tagRelations = craft()->db->createCommand()
			->select('entryId, tagId, dateCreated, dateUpdated, uid')
			->from('entrytags_entries')
			->queryAll(false);

		foreach ($tagRelations as &$relation)
		{
			array_unshift($relation, $fieldId);
		}

		$this->insertAll('relations', array('fieldId', 'parentId', 'childId', 'dateCreated', 'dateUpdated', 'uid'), $tagRelations, false);

		// Drop the old entrytags_entries table
		$this->dropTable('entrytags_entries');

		return true;
	}
}
