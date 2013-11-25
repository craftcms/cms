<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m131105_000004_matrix_blocks extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Rename the matrixrecordtypes table
		if (craft()->db->tableExists('matrixrecordtypes'))
		{
			MigrationHelper::renameTable('matrixrecordtypes', 'matrixblocktypes');
		}

		// Rename the matrixrecords table
		if (craft()->db->tableExists('matrixrecords'))
		{
			MigrationHelper::renameTable('matrixrecords', 'matrixblocks');
		}

		// Update any Matrix field contexts
		craft()->db->createCommand()
			->setText("UPDATE {{fields}} SET context = REPLACE(context, 'matrixRecordType:', 'matrixBlockType:')")
			->query();

		return true;
	}
}
