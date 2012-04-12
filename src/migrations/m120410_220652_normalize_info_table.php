<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m120410_220652_normalize_info_table extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 */
	public function safeUp()
	{
		$infoTable = $this->dbConnection->schema->getTable('{{info}}');

		$onlineExists = $infoTable->getColumn('online') !== null ? true : false;
		$onExists = $infoTable->getColumn('on') !== null ? true : false;

		// If they both exist, drop 'online'
		if ($onlineExists && $onExists)
		{
			$this->dropColumn('info', 'online');
		}

		// If only online exists, rename to 'on'
		if ($onlineExists && !$onExists)
		{
			$this->renameColumn('info', 'online', 'on');
		}
	}
}
