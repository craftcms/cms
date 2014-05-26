<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140603_000004_tokens extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->tableExists('tokens'))
		{
			// Create the tokens table
			$this->createTable('tokens', array(
				'token'      => array('maxLength' => 255, 'column' => ColumnType::Char, 'length' => 32, 'required' => true),
				'route'      => array('column' => ColumnType::Text),
				'usageLimit' => array('maxLength' => 4, 'decimals' => 0, 'unsigned' => true, 'length' => 3, 'column' => ColumnType::TinyInt),
				'usageCount' => array('maxLength' => 4, 'decimals' => 0, 'unsigned' => true, 'length' => 3, 'column' => ColumnType::TinyInt),
				'expiryDate' => array('column' => ColumnType::DateTime, 'required' => true),
			));
			$this->createIndex('tokens', 'token', true);
			$this->createIndex('tokens', 'expiryDate', false);
		}

		return true;
	}
}
