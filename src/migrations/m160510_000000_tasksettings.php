<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160510_000000_tasksettings extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Changing tasks table settings column to mediumtext.', LogLevel::Info, true);
		$this->alterColumn('tasks', 'settings', array('column' => ColumnType::MediumText));
		Craft::log('Done changing tasks table settings column to mediumtext.', LogLevel::Info, true);

		return true;
	}
}
