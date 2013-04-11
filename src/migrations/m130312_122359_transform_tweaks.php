<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130312_122359_transform_tweaks extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$assetTransformationsTable = $this->dbConnection->schema->getTable('{{assettransformations}}');
		$assetTransformsTable = $this->dbConnection->schema->getTable('{{assettransforms}}');

		if ($assetTransformationsTable && !$assetTransformsTable)
		{
			$this->dbConnection->createCommand()->renameTable('assettransformations', 'assettransforms');
			Craft::log('Successfully renamed `assettransformations` to `assettransforms`.', \CLogger::LEVEL_INFO);
			craft()->db->getSchema()->refresh();
		}
		else
		{
			Craft::log('Tried to rename `assettransformations` to `assettransforms`, but `assettransforms` already exists.', LogLevel::Warning);
		}

		// assettransforms is guaranteed to exist by this point.
		$this->alterColumn('assettransforms', 'width', 'INT(10) NULL');
		$this->alterColumn('assettransforms', 'height', 'INT(10) NULL');
		$this->alterColumn('assettransforms', 'mode', array('column' => ColumnType::Char, 'length' => 7, 'required' => true, 'default' => 'crop'));

		$this->update('assettransforms', array('mode' => 'fit'), 'mode = "scaleTo"');
		$this->update('assettransforms', array('mode' => 'crop'), 'mode = "scaleAnd"');
		$this->update('assettransforms', array('mode' => 'stretch'), 'mode = "stretch"');

		return true;
	}
}
