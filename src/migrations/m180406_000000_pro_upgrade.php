<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m180406_000000_pro_upgrade extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$info = craft()->getInfo();
		if ($info->edition == Craft::Client)
		{
			$info->edition = Craft::Pro;
			craft()->saveInfo($info);
		}

		craft()->cache->delete('licensedEdition');

		return true;
	}
}
