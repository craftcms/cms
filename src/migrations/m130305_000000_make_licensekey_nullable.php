<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130305_000000_make_licensekey_nullable extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->alterColumn('info', 'licenseKey', array('maxLength' => 255, 'length' => 36, 'column' => 'char'));
		return true;
	}
}
