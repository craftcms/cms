<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130402_205054_site_timezone_setting extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->addColumnAfter('info', 'timezone', array('column' => ColumnType::Varchar, 'length' => 30), 'siteUrl');
		return true;
	}
}
