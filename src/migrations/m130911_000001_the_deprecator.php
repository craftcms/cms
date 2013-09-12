<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130911_000001_the_deprecator extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->tableExists('deprecationlog'))
		{
			// Create the new deprecationlog table
			$this->createTable('deprecationlog', array(
				'key'               => array('column' => ColumnType::Varchar, 'required' => true),
				'fingerprint'       => array('column' => ColumnType::Varchar, 'required' => true),
				'message'           => array('column' => ColumnType::Varchar, 'required' => true),
				'deprecatedSince'   => array('column' => ColumnType::Varchar, 'maxLength' => 25, 'required' => true),
				'stackTrace'        => array('column' => ColumnType::Text,    'required' => true),
				'file'              => array('column' => ColumnType::Varchar, 'required' => true),
				'line'              => array('column' => ColumnType::Int,     'required' => true),
				'method'            => array('column' => ColumnType::Char,    'maxLength' => 150),
				'class'             => array('column' => ColumnType::Char,    'maxLength' => 150),
			));

			$this->createIndex('deprecationlog', 'key,fingerprint', true);

		}
		else
		{
			Craft::log('Tried to add the `deprecationlog` table, but it already exists.', LogLevel::Warning, true);
		}

		return true;
	}
}
