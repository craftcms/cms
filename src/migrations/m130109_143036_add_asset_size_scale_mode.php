<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130109_143036_add_asset_size_scale_mode extends \CDbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$assetSizesTable = blx()->db->schema->getTable('{{assetsizes}}');

		if (!$assetSizesTable->getColumn('scaleMode'))
		{
			blx()->db->createCommand()->addColumnAfter('assetsizes', 'scaleMode', array('type' => AttributeType::String, 'maxLength' => 100), 'width');
		}
		else
		{
			Blocks::log('Tried to add `scaleMode` column to the `assetsizes`, but it already exists.', \CLogger::LEVEL_WARNING);
		}
	}
}
