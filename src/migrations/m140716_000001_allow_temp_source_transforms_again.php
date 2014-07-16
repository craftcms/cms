<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140716_000001_allow_temp_source_transforms_again extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Do this again because for fresh 2.1 or earlier installs, these will still be set to NOT  NULL
		$this->alterColumn('assettransformindex', 'sourceId', array('column' => ColumnType::Int, 'required' => false));

		return true;
	}
}
