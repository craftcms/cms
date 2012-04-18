<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m120417_212359_add_name_column_to_plugins extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 */
	public function safeUp()
	{
		$pluginsTable = b()->db->schema->getTable('{{plugins}}');
		$pluginNameColumn = $pluginsTable->getColumn('name') !== null ? true : false;

		if (!$pluginNameColumn)
		{
			b()->db->createCommand()->addColumnBefore('plugins', 'name', AttributeType::Name, 'class');
		}
	}
}
