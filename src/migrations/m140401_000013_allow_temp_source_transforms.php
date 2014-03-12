<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_000013_allow_temp_source_transforms extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->alterColumn('assettransformindex', 'sourceId', array('column' => ColumnType::Int, 'required' => false));

		return true;
	}
}
