<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m121217_123212_add_assetsizes extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$record = new AssetSizeRecord('install');
		$record->createTable();
		$record->addForeignKeys();

		return true;
	}
}
