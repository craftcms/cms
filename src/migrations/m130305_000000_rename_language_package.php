<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130305_000000_rename_language_package extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$installedPackages = Craft::getPackages();
		$languageIndex = array_search('Language', $installedPackages);

		if ($languageIndex !== false)
		{
			$installedPackages[$languageIndex] = 'Localize';
		}

		craft()->db->createCommand()->update('info', array(
			'packages' => implode(',', $installedPackages))
		);

		return true;
	}
}
