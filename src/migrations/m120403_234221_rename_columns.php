<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m120403_234221_rename_columns extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 */
	public function safeUp()
	{
		$this->renameColumn('info', 'online', 'on');
		$this->renameColumn('entries', 'full_uri', 'uri');
	}
}
