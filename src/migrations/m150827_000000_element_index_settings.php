<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m150827_000000_element_index_settings extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Creating the elementindexsettings table', LogLevel::Info, true);

		craft()->db->createCommand()->createTable('elementindexsettings', array(
			'type'     => array('maxLength' => 150, 'column' => ColumnType::Varchar, 'required' => true),
			'settings' => array('column' => ColumnType::Text),
		), null, true);

		craft()->db->createCommand()->createIndex('elementindexsettings', 'type', true);

		Craft::log('Done creating the elementindexsettings table', LogLevel::Info, true);

		return true;
	}
}
