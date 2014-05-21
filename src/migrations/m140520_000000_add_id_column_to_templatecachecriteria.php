<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140520_000000_add_id_column_to_templatecachecriteria extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->columnExists('templatecachecriteria', 'id'))
		{
			// Add the 'id' PK to templatecachecriteria
			$this->addColumnBefore('templatecachecriteria', 'id', ColumnType::PK, 'cacheId');

			// Create an index on the 'type' column
			$this->createIndex('templatecachecriteria', 'type');
		}

		return true;
	}
}
