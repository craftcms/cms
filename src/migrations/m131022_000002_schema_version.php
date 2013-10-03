<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m131022_000002_schema_version extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$versionColumn = array('column' => ColumnType::Varchar, 'length' => 15, 'null' => false);
		$this->alterColumn('info', 'version', $versionColumn);
		$this->addColumnAfter('info', 'schemaVersion', $versionColumn, 'build');
		return true;
	}
}
