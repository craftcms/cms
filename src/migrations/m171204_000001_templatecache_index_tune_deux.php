<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m171204_000001_templatecache_index_tune_deux extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		MigrationHelper::dropIndexIfExists('templatecaches', array('expiryDate', 'cacheKey', 'locale'));
		Craft::log('Creating `cacheKey,locale,expiryDate` index on the templatecaches table...', LogLevel::Info, true);
		$this->createIndex('templatecaches', 'cacheKey,locale,expiryDate');
		MigrationHelper::dropIndexIfExists('templatecaches', array('expiryDate', 'cacheKey', 'locale', 'path'));
		Craft::log('Creating `cacheKey,locale,expiryDate,path` index on the templatecaches table...', LogLevel::Info, true);
		$this->createIndex('templatecaches', 'cacheKey,locale,expiryDate,path');

		return true;
	}
}
