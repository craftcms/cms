<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m151127_000000_clear_license_key_status extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Delete the cached license key status so Craft will refetch it with the new value
		craft()->cache->delete('licenseKeyStatus');

		return true;
	}
}
