<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130313_181630_transformations_to_transforms_part_deux extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Because of a bug in m130305_000006_transformations_to_transforms, this might have reported running successfully, but not actually ran.

		$assetTransformationsTable = $this->dbConnection->schema->getTable('{{assettransformations}}');
		$assetTransformsTable = $this->dbConnection->schema->getTable('{{assettransforms}}');

		if ($assetTransformationsTable && !$assetTransformsTable)
		{
			$this->dbConnection->createCommand()->renameTable('assettransformations', 'assettransforms');
			Craft::log('Successfully renamed `assettransformations` to `assettransforms`.');
		}
		else
		{
			Craft::log('Tried to rename `assettransformations` to `assettransforms`, but `assettransforms` already exists.', LogLevel::Warning);
		}

		return true;
	}
}
