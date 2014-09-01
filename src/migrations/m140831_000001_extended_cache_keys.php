<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140831_000001_extended_cache_keys extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->alterColumn(
			'templatecaches',
			'cacheKey',
			array('column' => ColumnType::Varchar, 'null' => false)
		);

		return true;
	}
}
