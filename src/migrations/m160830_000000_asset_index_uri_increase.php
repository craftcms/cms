<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160830_000000_asset_index_uri_increase extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Changing asset index data table uri column to text.', LogLevel::Info, true);
		$this->alterColumn('assetindexdata', 'uri', array('column' => ColumnType::Text));
		Craft::log('Done changing asset index data table uri column to text.', LogLevel::Info, true);

		return true;
	}
}
