<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m120410_222109_normalize_entries_table extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 */
	public function safeUp()
	{
		$entriesTable = $this->dbConnection->schema->getTable('{{entries}}');
		$fullUriExists = $entriesTable->getColumn('full_uri') !== null ? true : false;

		// If full_uri exists, rename to 'uri'
		if ($fullUriExists)
		{
			$this->renameColumn('entries', 'full_uri', 'uri');
		}
	}
}
