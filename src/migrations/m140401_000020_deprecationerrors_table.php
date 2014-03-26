<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_000020_deprecationerrors_table extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->tableExists('deprecationerrors'))
		{
			// Create the new deprecationerrors table
			$this->createTable('deprecationerrors', array(
				'key'               => array('column' => ColumnType::Varchar, 'null' => false),
				'fingerprint'       => array('column' => ColumnType::Varchar, 'null' => false),
				'lastOccurrence'    => array('column' => ColumnType::DateTime, 'null' => false),
				'file'              => array('column' => ColumnType::Varchar, 'null' => false),
				'line'              => array('column' => ColumnType::SmallInt, 'unsigned' => true, 'null' => false),
				'class'             => array('column' => ColumnType::Varchar),
				'method'            => array('column' => ColumnType::Varchar),
				'template'          => array('column' => ColumnType::Varchar),
				'templateLine'      => array('column' => ColumnType::SmallInt, 'unsigned' => true),
				'message'           => array('column' => ColumnType::Varchar),
				'traces'            => array('column' => ColumnType::Text),
			));

			$this->createIndex('deprecationerrors', 'key,fingerprint', true);

		}
		else
		{
			Craft::log('Tried to add the `deprecationerrors` table, but it already exists.', LogLevel::Info, true);
		}

		return true;
	}
}
