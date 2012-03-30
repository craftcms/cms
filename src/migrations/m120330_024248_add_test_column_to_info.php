<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m120330_024248_add_test_column_to_info extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 */
	public function safeUp()
	{
		$tablePrefix = b()->config->tablePrefix;
		$this->addColumn($tablePrefix.'info', 'test', 'int');
	}
}
