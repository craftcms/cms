<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m151110_000000_move_logo extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Moving the logo from storage/logo to storage/rebrand/logo', LogLevel::Info, true);

		IOHelper::rename(craft()->path->getStoragePath().'logo', craft()->path->getRebrandPath().'logo', true);

		Craft::log('Done moving the logo from storage/logo to storage/rebrand/logo', LogLevel::Info, true);

		return true;
	}
}
