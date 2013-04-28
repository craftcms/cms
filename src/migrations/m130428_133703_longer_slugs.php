<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130428_133703_longer_slugs extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->alterColumn('entries_i18n', 'slug', array('column' => ColumnType::Varchar, 'length' => 255, 'null' => false));

		return true;
	}
}
