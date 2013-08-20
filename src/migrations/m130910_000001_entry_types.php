<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130910_000001_entry_types extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Create the new entrytypes table
		$this->createTable('entrytypes', array(
			'sectionId'     => array('column' => ColumnType::Int,     'null' => false),
			'fieldLayoutId' => array('column' => ColumnType::Int),
			'name'          => array('column' => ColumnType::Varchar, 'null' => false),
			'handle'        => array('column' => ColumnType::Varchar, 'null' => false),
			'titleLabel'    => array('column' => ColumnType::Varchar),
		));
		$this->createIndex('entrytypes', 'name,sectionId', true);
		$this->createIndex('entrytypes', 'handle,sectionId', true);
		$this->addForeignKey('entrytypes', 'sectionId', 'sections', 'id', 'CASCADE', null);
		$this->addForeignKey('entrytypes', 'fieldLayoutId', 'fieldlayouts', 'id', 'SET NULL', null);

		// Add the 'typeId' column to the entries table
		$this->addColumnAfter('entries', 'typeId', array('column' => ColumnType::Int, 'null' => false), 'sectionId');

		// Create an entry type for each section

		$sections = craft()->db->createCommand()
			->select('id, fieldLayoutId, name, handle, titleLabel')
			->from('sections')
			->queryAll();

		foreach ($sections as $section)
		{
			$this->insert('entrytypes', array(
				'sectionId'     => $section['id'],
				'fieldLayoutId' => $section['fieldLayoutId'],
				'name'          => $section['name'],
				'handle'        => $section['handle'],
				'titleLabel'    => $section['titleLabel']
			));

			$entryTypeId = craft()->db->getLastInsertID();

			// Update the existing entries
			$this->update('entries', array(
				'typeId' => $entryTypeId
			), array(
				'sectionId' => $section['id']
			));
		}

		// Why not.
		$this->delete('entries', 'typeId = 0');

		// Now add the index and FK
		$this->createIndex('entries', 'typeId', false);
		$this->addForeignKey('entries', 'typeId', 'entrytypes', 'id', 'CASCADE', null);

		// Now delete the old sections.fieldLayoutId and titleLabel columns
		MigrationHelper::dropForeignKeyIfExists('sections', array('fieldLayoutId'));
		$this->dropColumn('sections', 'fieldLayoutId');
		$this->dropColumn('sections', 'titleLabel');

		return true;
	}
}
