<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m131209_000000_remove_recent_transform_indexes extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// The last build may have indexed some transforms without actually creating them
		$this->delete('assettransformindex', "dateCreated >= '2013-12-06 00:00:00'");

		return true;
	}
}
