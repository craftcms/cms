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
		craft()->db->createCommand()->truncateTable('rackspaceaccess');

		return true;
	}
}
