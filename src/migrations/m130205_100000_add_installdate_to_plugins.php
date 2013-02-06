<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130205_100000_add_installdate_to_plugins extends DbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$pluginsTable = blx()->db->schema->getTable('{{plugins}}');

		if ($pluginsTable)
		{
			if (!$pluginsTable->getColumn('installDate'))
			{
				blx()->db->createCommand()->addColumnAfter('plugins', 'installDate', array(AttributeType::DateTime, 'required' => true), 'settings');
				blx()->db->createCommand("UPDATE `{$pluginsTable->name}` SET `installDate` = `dateCreated`;")->execute();
			}
			else
			{
				Blocks::log('The `installDate` column already exists in the `plugins` table.', \CLogger::LEVEL_WARNING);
			}
		}
		else
		{
			Blocks::log('The `plugins` table is missing. No idea what is going on here.', \CLogger::LEVEL_WARNING);
		}
	}
}
