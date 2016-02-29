<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160229_000000_set_default_entry_statuses extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Only do anything if this is not a localized site
		if (!craft()->isLocalized())
		{
			// Set all section locales' enabledByDefault to true
			$this->update('sections_i18n', array('enabledByDefault' => '1'));
		}

		return true;
	}
}
