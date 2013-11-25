<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m131105_000003_field_contexts extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Add the fields.context column
		if (!craft()->db->columnExists('fields', 'context'))
		{
			$this->addColumnAfter('fields', 'context', array('default' => 'global', 'null' => false), 'handle');
		}

		// Replace the unique index on 'handle' with one on both 'handle' and 'context'
		MigrationHelper::dropIndexIfExists('fields', array('handle'), true);
		MigrationHelper::dropIndexIfExists('fields', array('handle', 'context'), true);
		$this->createIndex('fields', 'handle,context', true);

		return true;
	}
}
