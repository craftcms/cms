<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130109_143036_addAssetSizeScaleMode extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{

		blx()->db->createCommand()->addColumnAfter('assetsizes', 'scaleMode', array('type' => AttributeType::String, 'maxLength' => 100), 'width');
		return true;
	}
}
