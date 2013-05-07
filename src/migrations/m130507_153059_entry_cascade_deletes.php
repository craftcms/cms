<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130507_153059_entry_cascade_deletes extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// entries
		$this->dropForeignKey('entries', 'id');
		$this->dropForeignKey('entries', 'authorId');
		$this->dropForeignKey('entries', 'sectionId');

		$this->addForeignKey('entries', 'id', 'elements', 'id', 'CASCADE');
		$this->addForeignKey('entries', 'authorId', 'users', 'id', 'CASCADE');
		$this->addForeignKey('entries', 'sectionId', 'sections', 'id', 'CASCADE');

		// entrydrafts
		$this->dropForeignKey('entrydrafts', 'creatorId');
		$this->dropForeignKey('entrydrafts', 'entryId');
		$this->dropForeignKey('entrydrafts', 'sectionId');

		$this->addForeignKey('entrydrafts', 'creatorId', 'users', 'id', 'CASCADE');
		$this->addForeignKey('entrydrafts', 'entryId', 'entries', 'id', 'CASCADE');
		$this->addForeignKey('entrydrafts', 'sectionId', 'sections', 'id', 'CASCADE');

		// entryversions
		$this->dropForeignKey('entryversions', 'creatorId');
		$this->dropForeignKey('entryversions', 'entryId');
		$this->dropForeignKey('entryversions', 'sectionId');

		$this->addForeignKey('entryversions', 'creatorId', 'users', 'id', 'CASCADE');
		$this->addForeignKey('entryversions', 'entryId', 'entries', 'id', 'CASCADE');
		$this->addForeignKey('entryversions', 'sectionId', 'sections', 'id', 'CASCADE');

		return true;
	}
}
