<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130910_000004_no_parent_id extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$entriesTable = $this->dbConnection->schema->getTable('{{entries}}');

		if ($entriesTable->getColumn('parentId') !== null)
		{
			$this->dropForeignKey('entries', 'parentId', 'entries', 'id');
			$this->dropColumn('entries', 'parentId');
		}

		return true;
	}
}
