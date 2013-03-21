<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130320_222723_create_searchindex extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->tableExists('searchindex'))
		{
			// Taking the scenic route here so we can get to MysqlSchema's $engine argument
			$table = DbHelper::addTablePrefix('searchindex');

			$columns = array(
				'elementId' => DbHelper::generateColumnDefinition(array('column' => ColumnType::Int,    'null' => false)),
				'fieldId'   => DbHelper::generateColumnDefinition(array('column' => ColumnType::Int,    'null' => false)),
				'locale'    => DbHelper::generateColumnDefinition(array('column' => ColumnType::Locale, 'null' => false)),
				'keywords'  => DbHelper::generateColumnDefinition(array('column' => ColumnType::Text,   'null' => false)),
			);

			$this->execute(craft()->db->getSchema()->createTable($table, $columns, null, 'MyISAM'));

			// Give it a composite primary key
			$this->addPrimaryKey('searchindex', 'elementId,fieldId,locale');

			// Add the FULLTEXT index on `keywords`
			$this->execute('CREATE FULLTEXT INDEX ' .
				craft()->db->getSchema()->quoteTableName(DbHelper::getIndexName('searchindex', 'keywords')).' ON ' .
				craft()->db->getSchema()->quoteTableName($table).' ' .
				'('.craft()->db->getSchema()->quoteColumnName('keywords').')'
			);
		}

		return true;
	}
}
