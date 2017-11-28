<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m171117_000001_templatecache_index_tune extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Creating `expiryDate,cacheKey,locale` index on the templatecaches table...', LogLevel::Info, true);
		$this->createIndex('templatecaches', 'expiryDate,cacheKey,locale');
		Craft::log('Done creating `expiryDate,cacheKey,locale` index on the templatecaches table...', LogLevel::Info, true);

		return true;
	}
}
