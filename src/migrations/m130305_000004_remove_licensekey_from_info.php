<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130305_000004_remove_licensekey_from_info extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$infoTable = $this->dbConnection->schema->getTable('{{info}}');

		if ($infoTable)
		{
			if (($licenseKeyColumn = $infoTable->getColumn('licenseKey')) !== null)
			{
				$this->dropColumn('info', 'licenseKey');
				Craft::log('Dropped the `licenseKey` column from the `info` table.');
			}
			else
			{
				Craft::log('Could not find a `licenseKey` column in the `info` table.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Could not find an `info` table. Wut?', LogLevel::Error);
		}
	}
}
