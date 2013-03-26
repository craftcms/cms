<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130325_181058_shunnedmessages extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$shunnedMessagesTable = $this->dbConnection->schema->getTable('{{shunnedmessages}}');

		if (!$shunnedMessagesTable)
		{
			// Create the shunnedmessages table
			$this->createTable('shunnedmessages', array(
				'userId'     => array('column' => ColumnType::Int, 'null' => false),
				'message'    => array('column' => ColumnType::Varchar, 'null' => false),
				'expiryDate' => array('column' => ColumnType::DateTime),
			));

			$this->createIndex('shunnedmessages', 'userId,message', true);
			$this->addForeignKey('shunnedmessages', 'userId', 'users', 'id', 'CASCADE');

			Craft::log('Sucessfully created the `shunnedmessages` table.', \CLogger::LEVEL_INFO);
		}
		else
		{
			Craft::log('Tried to create the `shunnedmessages` table, but it already exists.', \CLogger::LEVEL_WARNING);
		}

		return true;
	}
}
