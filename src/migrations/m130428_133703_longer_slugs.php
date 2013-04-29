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
		$entriesI18nTable = $this->dbConnection->schema->getTable('{{entries_i18n}}');

		if ($entriesI18nTable)
		{
			if (($slugColumn = $entriesI18nTable->getColumn('slug')) !== null)
			{
				Craft::log('Altering the `slug` column from `entries_i18n`', LogLevel::Info, true);
				$this->alterColumn('entries_i18n', 'slug', array('column' => ColumnType::Varchar, 'length' => 255, 'null' => false));
				Craft::log('Altered the `slug` column from `entries_i18n`', LogLevel::Info, true);
			}
			else
			{
				Craft::log('Tried to alter the `slug` column from `entries_i18n`, but the column is missing.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Tried to alter the `slug` column from `entries_i18n`, but the table does not exist!', LogLevel::Error);
		}

		return true;
	}
}
