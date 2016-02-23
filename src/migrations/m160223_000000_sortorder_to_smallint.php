<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160223_000000_sortorder_to_smallint extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Adjusting sortOrder columns for all tables.', LogLevel::Info, true);

		$this->alterColumn('assetsources', 'sortOrder', array('column' => ColumnType::SmallInt, 'unsigned' => true));
		$this->alterColumn('entrytypes', 'sortOrder', array('column' => ColumnType::SmallInt, 'unsigned' => true));
		$this->alterColumn('fieldlayoutfields', 'sortOrder', array('column' => ColumnType::SmallInt, 'unsigned' => true));
		$this->alterColumn('fieldlayouttabs', 'sortOrder', array('column' => ColumnType::SmallInt, 'unsigned' => true));
		$this->alterColumn('locales', 'sortOrder', array('column' => ColumnType::SmallInt, 'unsigned' => true));
		$this->alterColumn('matrixblocks', 'sortOrder', array('column' => ColumnType::SmallInt, 'unsigned' => true));
		$this->alterColumn('matrixblocktypes', 'sortOrder', array('column' => ColumnType::SmallInt, 'unsigned' => true));
		$this->alterColumn('routes', 'sortOrder', array('column' => ColumnType::SmallInt, 'unsigned' => true));
		$this->alterColumn('widgets', 'sortOrder', array('column' => ColumnType::SmallInt, 'unsigned' => true));

		Craft::log('Done adjusting sortOrder columns for all tables.', LogLevel::Info, true);

		return true;
	}
}
