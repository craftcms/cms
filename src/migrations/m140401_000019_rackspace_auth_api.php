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
		// Check if Racksspace sources exist. If they, update their settings and display the warning.

		$displayMessage = false;
		foreach ($sources as $source)
		{
			if ($source->type == "Rackspace")
			{
				$displayMessage = true;

				$settings = $source->settings;
				$settings['region'] = "-";
				unset($settings['location']);

				$source->settings = $settings;
				craft()->assetSources->saveSource($source);
			}
		}
		if ($displayMessage)
		{
			craft()->db->createCommand()->truncateTable('rackspaceaccess');
			$message = "Rackspace Authorization API has been update to v2. For your Rackspace based sources to work correctly, please, review and update their settings.";
			craft()->userSession->addJsFlash('Craft.cp.displayMessageModal("'.Craft::t($message).'");');
		}

		return true;
	}
}
