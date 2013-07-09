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
		// Create the tagsets table
		craft()->db->createCommand()->createTable('tagsets', array(
			'name'          => array('maxLength' => 100, 'column' => ColumnType::Varchar, 'null' => false),
			'handle'        => array('maxLength' => 45, 'column' => ColumnType::Char, 'null' => false),
			'fieldLayoutId' => array('column' => ColumnType::Int, 'length' => 10, 'unsigned' => false),
		));
		$this->createIndex('tagsets', 'name', true);
		$this->createIndex('tagsets', 'handle', true);

		// Create the Default tag group
		$this->insert('tagsets', array(
			'name' => 'Default',
			'handle' => 'default'
		));

		$tagSetId = craft()->db->getLastInsertID();

		// Rename the entrytags table
		MigrationHelper::renameTable('entrytags', 'tags');

		// Convert the tags to elements
		MigrationHelper::makeElemental('tags', 'Tag');

		// Make some tweaks on the tags table
		$this->alterColumn('tags', 'name', array('column' => ColumnType::Varchar));
		$this->dropColumn('tags', 'count');
		$this->addColumnBefore('tags', 'setId', array('column' => ColumnType::Int), 'name');
		$this->dropIndex('tags', 'name', true);

		// Place all current tags into the Default group
		$this->update('tags', array(
			'setId' => $tagSetId
		));

		$this->createIndex('tags', 'setId, name', true);
		$this->addForeignKey('tags', 'setId', 'tagsets', 'id', 'CASCADE', null);

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
