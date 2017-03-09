<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m161109_000000_index_shuffle extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Order is important.
		Craft::log('Dropping `expiryDate,cacheKey,locale,path` index on the templatecaches table.', LogLevel::Info, true);
		MigrationHelper::dropIndexIfExists('templatecaches', array('expiryDate', 'cacheKey', 'locale', 'path'));
		MigrationHelper::dropIndexIfExists('templatecaches', array('locale', 'cacheKey', 'path', 'expiryDate'));

		Craft::log('Creating `locale,cacheKey,path,expiryDate` index on the templatecaches table.', LogLevel::Info, true);
		$this->createIndex('templatecaches', 'locale,cacheKey,path,expiryDate');

		return true;
	}
}
