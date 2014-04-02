<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_000019_rackspace_auth_api extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Apparently it's possible for the rackspaceaccess table to not exist, as is the case with the latest 1.x ontherocks.sql
		if (!craft()->db->tableExists('rackspaceaccess'))
		{
			craft()->db->createCommand()->createTable('rackspaceaccess', array(
				'connectionKey'  => array('column' => ColumnType::Varchar, 'required' => true),
				'token'          => array('column' => ColumnType::Varchar, 'required' => true),
				'storageUrl'     => array('column' => ColumnType::Varchar, 'required' => true),
				'cdnUrl'         => array('column' => ColumnType::Varchar, 'required' => true),
			));

			craft()->db->createCommand()->createIndex('rackspaceaccess', 'connectionKey', true);

			craft()->db->getSchema()->refresh();
		}

		$sources = craft()->assetSources->getAllSources();

		// Check if Racksspace sources exist. If they do, update their settings.
		foreach ($sources as $source)
		{
			if ($source->type == "Rackspace")
			{
				$settings = $source->settings;
				$settings['region'] = "-";
				unset($settings['location']);

				$source->settings = $settings;
				craft()->assetSources->saveSource($source);
			}
		}

		$this->truncateTable('rackspaceaccess');

		return true;
	}
}
