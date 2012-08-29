<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m120416_203553_add_plugins_schema extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 */
	public function safeUp()
	{
		$pluginsTable = blx()->db->getSchema()->getTable('{{plugins}}');

		// If plugins doesn't exist, create it
		if (!$pluginsTable)
		{
			$this->createTable('plugins', array(
				'class'      => PropertyType::ClassName,
				'version'    => PropertyType::Version,
				'enabled'    => array('type' => PropertyType::Boolean, 'default' => true)
			));
		}

		$pluginSettingsTable = blx()->db->getSchema()->getTable('{{pluginsettings}}');

		// Check for the plugin settings table.
		if (!$pluginSettingsTable)
		{
			blx()->db->createCommand()->createSettingsTable('pluginsettings', 'plugins', 'pluginsettings_plugins');
		}

		$widgetsTable = blx()->db->getSchema()->getTable('{{widgets}}');
		$pluginIdColumn = $widgetsTable->getColumn('plugin_id') !== null ? true : false;

		if (!$pluginIdColumn)
		{
			blx()->db->createCommand()->addColumnAfter('widgets', 'plugin_id', array('type' => PropertyType::Int, 'required' => false), 'user_id');
			$this->createIndex('widgets_plugins_fk', 'widgets', 'plugin_id');
			$this->addForeignKey('widgets_plugins_fk', 'widgets', 'plugin_id', 'plugins', 'id');
		}

	}
}
