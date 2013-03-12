<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130206_100000_add_installdate_to_plugins extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$pluginsTable = $this->dbConnection->schema->getTable('{{plugins}}');

		if ($pluginsTable)
		{
			if (!$pluginsTable->getColumn('installDate'))
			{
				$this->addColumnAfter('plugins', 'installDate', array(AttributeType::DateTime, 'required' => true), 'settings');
				craft()->db->createCommand("UPDATE `{$pluginsTable->name}` SET `installDate` = `dateCreated`;")->execute();
				Craft::log('Successfully added `installDate` column and populated it.');
			}
			else
			{
				Craft::log('The `installDate` column already exists in the `plugins` table.', \CLogger::LEVEL_WARNING);
			}
		}
		else
		{
			Craft::log('The `plugins` table is missing. No idea what is going on here.', \CLogger::LEVEL_WARNING);
		}
	}
}
