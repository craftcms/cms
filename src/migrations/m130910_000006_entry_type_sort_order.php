<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130910_000006_entry_type_sort_order extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$entryTypesTable = $this->dbConnection->schema->getTable('{{entrytypes}}');

		if ($entryTypesTable->getColumn('sortOrder') === null)
		{
			// Add the sortOrder column to the entrytypes table
			$this->addColumn('entrytypes', 'sortOrder', array('column' => ColumnType::TinyInt));
		}

		return true;
	}
}
