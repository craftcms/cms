<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m170612_000000_route_index_shuffle extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Dropping `urlPattern` (unique) index on the routes table.', LogLevel::Info, true);
		MigrationHelper::dropIndexIfExists('routes', array('urlPattern'), true);

		Craft::log('Creating `routes` index on the routes table.', LogLevel::Info, true);
		$this->createIndex('routes', 'urlPattern');

		return true;
	}
}
