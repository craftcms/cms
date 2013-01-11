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

		// renameTable doesn't support table name like {{this}}, so we have to add the prefix ourselves
		$command = blx()->db->createCommand(blx()->db->schema->renameTable(blx()->db->tablePrefix.'assetsizes', blx()->db->tablePrefix.'assettransformations'));
		$command->execute();
	}
}
