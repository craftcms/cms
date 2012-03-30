<?php
use Blocks\Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m120330_031201_remove_test_column_from_info extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 */
	public function safeUp()
	{
		$tablePrefix = Blocks::app()->config->tablePrefix;
		$this->dropColumn($tablePrefix.'info', 'test');
	}
}
