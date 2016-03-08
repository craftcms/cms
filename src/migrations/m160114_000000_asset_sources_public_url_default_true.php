<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160114_000000_asset_sources_public_url_default_true extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Adding the publicURLs settings to Assets Sources that defaults to true', LogLevel::Info, true);

		$sourceRows = craft()->db->createCommand()
				->select('id, settings')
				->from('assetsources')
				->queryAll();

		foreach ($sourceRows as $source)
		{
			$settings = JsonHelper::decode($source['settings']);

			// This should always be true, but to be on the safe side.
			if (!isset($settings['publicURLs']))
			{
				$settings['publicURLs'] = true;
				$settings = JsonHelper::encode($settings);

				craft()->db->createCommand()->update(
					'assetsources',
					array('settings' => $settings),
					'id = :id',
					array(':id' => $source['id'])
				);
			}
		}

		Craft::log('Done adding the publicURLs settings to Assets Sources that defaults to true', LogLevel::Info, true);

		return true;
	}
}
