<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\migrations;

use craft\app\db\BaseMigration;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140815_000001_add_format_to_transforms extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{

		// Allow transforms to have a format
		$this->addColumnAfter('assettransforms', 'format', [ColumnType::Varchar, 'required' => false], 'width');

		return true;
	}
}
