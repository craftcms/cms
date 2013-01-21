<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m121217_123212_add_assetsizes extends DbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Create the assetsizes table
		blx()->db->createCommand()->createTable('assetsizes', array(
		    'name'   => array('required' => true),
		    'handle' => array('required' => true),
		    'height' => array('maxLength' => 11, 'decimals' => 0, 'required' => true, 'unsigned' => false, 'length' => 10, 'column' => 'integer'),
		    'width'  => array('maxLength' => 11, 'decimals' => 0, 'required' => true, 'unsigned' => false, 'length' => 10, 'column' => 'integer'),
		));

		// Add the indexes
		blx()->db->createCommand()->createIndex('assetsizes', 'name', true);
		blx()->db->createCommand()->createIndex('assetsizes', 'handle', true);

		return true;
	}
}
