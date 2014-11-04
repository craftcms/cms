<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m141030_000001_drop_structure_move_permission extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Dropping the movePermission column from the structures table...', LogLevel::Info, true);

		$this->dropColumn('structures', 'movePermission');

		Craft::log('Done dropping the movePermission column from the structures table.', LogLevel::Info, true);

		return true;
	}
}
