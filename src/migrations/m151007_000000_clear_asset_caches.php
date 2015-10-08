<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m151007_000000_clear_asset_caches extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Clearing asset caches', LogLevel::Info, true);

		$path = craft()->path->getRuntimePath().'assets';
		IOHelper::clearFolder($path);

		Craft::log('Done clearing asset caches', LogLevel::Info, true);

		return true;
	}
}
