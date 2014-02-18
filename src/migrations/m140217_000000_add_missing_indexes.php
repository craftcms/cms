<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140217_000000_add_missing_indexes extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (craft()->db->tableExists('locales'))
		{
			Craft::log('Adding index to sortOrder column of the locales table.', LogLevel::Info, true);
			$this->createIndex('locales', 'sortOrder');
		}
		else
		{
			Craft::log('Tried to add index to sortOrder column of the locales table, but it does not exist.  Wut?', LogLevel::Error);
		}

		if (craft()->db->tableExists('fields'))
		{
			Craft::log('Adding index to context column of the fields table.', LogLevel::Info, true);
			$this->createIndex('fields', 'context');
		}
		else
		{
			Craft::log('Tried to add index to context column of the fields table, but it does not exist.  Wut?', LogLevel::Error);
		}

		return true;
	}
}
