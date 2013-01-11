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

		if (!$assetSizesTable->getColumn('mode'))
		{
			blx()->db->createCommand()->addColumnAfter('assetsizes', 'mode', array(AttributeType::Enum, 'values' => array('scaleToFit', 'scaleAndCrop', 'stretchToFit'),  'default' => 'scaleToFit'), 'width');
		}
		else
		{
			Blocks::log('Tried to add `mode` column to the `assetsizes`, but it already exists.', \CLogger::LEVEL_WARNING);
		}

		if ($assetSizesTable && !blx()->db->schema->getTable('{{assettransformations}}'))
		{
			// Drop the old indexes.
			blx()->db->createCommand()->dropIndex('assetsizes', 'name', true);
			blx()->db->createCommand()->dropIndex('assetsizes', 'handle', true);

			// Rename the table.
			blx()->db->createCommand()->renameTable('assetsizes', 'assettransformations');

			// Add the indexes back.  They'll have proper names now.
			blx()->db->createCommand()->createIndex('assettransformations', 'name', true);
			blx()->db->createCommand()->createIndex('assettransformations', 'handle', true);
		}
		else
		{
			Blocks::log('Tried to rename `assetsizes` to `assettransformations`, but `assettransformations`  already exists.', \CLogger::LEVEL_WARNING);
		}
	}
}
