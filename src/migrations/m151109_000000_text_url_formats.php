<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m151109_000000_text_url_formats extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Updating the URL format columns', LogLevel::Info, true);

		$columns = array(
			'categorygroups_i18n' => array('urlFormat', 'nestedUrlFormat'),
			'sections_i18n'       => array('urlFormat', 'nestedUrlFormat'),
		);

		foreach ($columns as $table => $tableColumns)
		{
			foreach ($tableColumns as $column)
			{
				$this->alterColumn($table, $column, array('column' => ColumnType::Text));
			}
		}

		Craft::log('Done updating the URL format columns', LogLevel::Info, true);

		return true;
	}
}
