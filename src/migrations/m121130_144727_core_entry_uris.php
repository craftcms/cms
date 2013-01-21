<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m121130_144727_core_entry_uris extends DbMigration
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
			$this->_createIndex('entries_uri_unique_idx', 'entries', 'uri', true);

			// Fill 'er up
			blx()->db->createCommand()->setText('UPDATE '.blx()->db->tablePrefix.'entries SET uri = CONCAT("blog/", slug)')->execute();
		}

		return true;
	}

	/**
	 * @param      $name
	 * @param      $table
	 * @param      $columns
	 * @param bool $unique
	 * @return int
	 */
	private function _createIndex($name, $table, $columns, $unique = false)
	{
		$name = md5(blx()->db->tablePrefix.$name);
		$table = DbHelper::addTablePrefix($table);
		return blx()->db->createCommand()->setText(blx()->db->getSchema()->createIndex($name, $table, $columns, $unique))->execute();
	}
}
