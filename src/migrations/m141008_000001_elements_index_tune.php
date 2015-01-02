<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\migrations;

use craft\app\Craft;
use craft\app\db\BaseMigration;
use craft\app\enums\LogLevel;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m141008_000001_elements_index_tune extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Dropping `archived` index on the elements table...', LogLevel::Info, true);
		$this->dropIndex('elements', 'archived');
		Craft::log('Done dropping `archived` index on the elements table.', LogLevel::Info, true);

		Craft::log('Creating `archived, dateCreated`` index on the elements table...', LogLevel::Info, true);
		$this->createIndex('elements', 'archived, dateCreated');
		Craft::log('Done creating `archived, dateCreated`` index on the elements table...', LogLevel::Info, true);

		return true;
	}
}
