<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\migrations;

use craft\app\db\BaseMigration;
use craft\app\enums\ColumnType;

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
