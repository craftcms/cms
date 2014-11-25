<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m141124_000001_user_week_start_day extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Adding weekStartDay column to users table...', LogLevel::Info, true);
		$this->addColumnAfter('users', 'weekStartDay', array(ColumnType::TinyInt, 'unsigned', 'required' => true, 'default' => '0'), 'preferredLocale');

		return true;
	}
}
