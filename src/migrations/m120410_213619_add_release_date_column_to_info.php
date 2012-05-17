<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m120410_213619_add_release_date_column_to_info extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 */
	public function safeUp()
	{
		$infoTable = $this->getDbConnection()->getSchema()->getTable('{{info}}');
		$releaseDateExists = $infoTable->getColumn('release_date') !== null ? true : false;

		if (!$releaseDateExists)
		{
			$this->addColumnAfter('info', 'release_date', array(AttributeType::Int, 'required' => true), 'build');
		}
	}
}
