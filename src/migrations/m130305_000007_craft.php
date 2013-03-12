<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130305_000007_craft extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// autoUpdateBlocks => performUpdates
		$this->update('userpermissions',
			array('name' => 'performupdates'),
			array('in', 'name', array('autoupdateblocks', 'autoupdatecraft'))
		);

		return true;
	}
}
