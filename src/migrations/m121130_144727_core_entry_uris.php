<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m121130_144727_core_entry_uris extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			// Add the URI column after Slug
			$config = array('column' => ColumnType::Varchar, 'maxLength' => 150);
			blx()->db->createCommand()->addColumnAfter('entries', 'uri', $config, 'slug');

			// Add the unique constraint
			blx()->db->createCommand()->createIndex('entries_uri_unique_idx', 'entries', 'uri', true);

			// Fill 'er up
			blx()->db->createCommand()->setText('UPDATE '.blx()->db->tablePrefix.'entries SET uri = CONCAT("blog/", slug)')->execute();
		}

		return true;
	}
}
