<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m121105_213856_add_users_photos_column extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		blx()->db->createCommand()->addColumnAfter('users', 'photo', array('column' => ColumnType::Varchar, 'maxlength' => 50), 'username');

		return true;
	}
}
