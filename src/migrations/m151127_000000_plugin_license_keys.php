<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m151127_000000_plugin_license_keys extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->columnExists('plugins', 'licenseKey'))
		{
			Craft::log('Adding licenseKey column to plugins table', LogLevel::Info, true);
			$this->addColumnAfter('plugins', 'licenseKey', array('column' => ColumnType::Char, 'length' => 24), 'schemaVersion');
			Craft::log('Done adding licenseKey column to plugins table.', LogLevel::Info, true);
		}

		if (!craft()->db->columnExists('plugins', 'licenseKeyStatus'))
		{
			Craft::log('Adding licenseKeyStatus column to plugins table', LogLevel::Info, true);
			$this->addColumnAfter('plugins', 'licenseKeyStatus', array('column' => ColumnType::Enum, 'values' => array('valid', 'invalid', 'mismatched', 'unknown'), 'default' => 'unknown', 'null' => false), 'licenseKey');
			Craft::log('Done adding licenseKeyStatus column to plugins table.', LogLevel::Info, true);
		}

		return true;
	}
}
