<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m141126_000001_user_week_start_day extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Adding weekStartDay column to users table...', LogLevel::Info, true);

		$column = array(ColumnType::TinyInt, 'unsigned', 'required' => true, 'default' => '0');

		if (craft()->db->columnExists('users', 'weekStartDay'))
		{
			$this->update(
				'users',
				array('weekStartDay' => '0'),
				'weekStartDay is null'
			);

			$this->alterColumn('users', 'weekStartDay', $column);
		}
		else
		{
			$this->addColumnAfter('users', 'weekStartDay', $column, 'preferredLocale');
		}

		return true;
	}
}
