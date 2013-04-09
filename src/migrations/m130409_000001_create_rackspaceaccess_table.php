<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130409_000001_create_rackspaceaccess_table extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$rackspaceAccessTable = $this->dbConnection->schema->getTable('{{rackspaceaccess}}');

		if (!$rackspaceAccessTable)
		{
			Craft::log('Creating the Rackspace access table.');

			craft()->db->createCommand()->createTable('rackspaceaccess', array(
				'connectionKey'  => array('column' => ColumnType::Varchar, 'required' => true),
				'token'          => array('column' => ColumnType::Varchar, 'required' => true),
				'storageUrl'     => array('column' => ColumnType::Varchar, 'required' => true),
				'cdnUrl'         => array('column' => ColumnType::Varchar, 'required' => true),
			));

			craft()->db->createCommand()->createIndex('rackspaceaccess', 'connectionKey', true);

			Craft::log('Finished creating the Rackspace access table.');
		}
		else
		{
			Craft::log('Tried to create the `rackspaceaccess` table, but it already exists.', \CLogger::LEVEL_WARNING);
		}

		return true;
	}
}
