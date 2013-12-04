<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m131203_000000_allow_orphan_assets extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->alterColumn('assetfolders', 'sourceId', array('column' => ColumnType::Int, 'required' => false));
		$this->alterColumn('assetfiles', 'sourceId', array('column' => ColumnType::Int, 'required' => false));

		return true;
	}
}
