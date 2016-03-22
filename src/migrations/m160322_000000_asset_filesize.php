<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160322_000000_asset_filesize extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Increasing the max Asset file size in “assetfiles” table.', LogLevel::Info, true);
		$this->alterColumn('assetfiles', 'size', array('column' => ColumnType::BigInt, 'unsigned' => true));

		Craft::log('Increasing the max Asset file size in “assetindexdata” table.', LogLevel::Info, true);
		$this->alterColumn('assetindexdata', 'size', array('column' => ColumnType::BigInt, 'unsigned' => true));

		return true;
	}
}
