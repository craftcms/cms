<?php
use Blocks\Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m120330_180818_add_column_to_info extends CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 */
	public function safeUp()
	{
		$tablePrefix = Blocks::app()->config->tablePrefix;
		$this->addColumn($tablePrefix.'info', 'test2', 'int');
	}
}
