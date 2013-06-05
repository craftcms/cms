<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130605_221646_add_enabled_to_widgets extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$widgetsTable = $this->dbConnection->schema->getTable('{{widgets}}');

		if ($widgetsTable)
		{
			if (($enabledColumn = $widgetsTable->getColumn('enabled')) == null)
			{
				Craft::log('Adding `enabled` column to the `widgets` table.', LogLevel::Info, true);
				$this->addColumnAfter('widgets', 'enabled', array(AttributeType::Bool, 'default' => true), 'settings');
				Craft::log('Added `enabled` column to the `widgets` table.', LogLevel::Info, true);
			}
			else
			{
				Craft::log('Tried to add a `enabled` column to the `widgets` table, but there is already one there.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Could not find an `widgets` table. Wut?', LogLevel::Error);
		}
	}
}
