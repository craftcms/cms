<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130315_180929_fix_assettransforms_mode extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$assetTransformsTable = $this->dbConnection->schema->getTable('{{assettransforms}}');

		if ($assetTransformsTable)
		{
			if (($modeColumn = $assetTransformsTable->getColumn('mode')) !== null)
			{
				$modeTempColumn = $assetTransformsTable->getColumn('mode_temp');

				if (!$modeTempColumn)
				{
					$this->renameColumn('assettransforms', 'mode', 'mode_temp');
					$this->addColumnAfter('assettransforms', 'mode', array(AttributeType::Enum, 'values' => array('stretch', 'fit', 'crop'), 'required' => true, 'default' => 'crop'), 'mode_temp');
				}
				else
				{
					Craft::log('Tried to clean up `assettransforms` table and the `mode` column, but `mode_temp` already exists. Attempting to gracefully recover.', \CLogger::LEVEL_WARNING);
				}

				$this->update('assettransforms', array('mode' => 'fit'), 'mode_temp = "scaleToFit" OR mode_temp = "scaleTo"');
				$this->update('assettransforms', array('mode' => 'crop'), 'mode_temp = "scaleAndCrop" OR mode_temp = "scaleAnd"');
				$this->update('assettransforms', array('mode' => 'stretch'), 'mode_temp = "stretchToFit" OR mode_temp = "stretch"');

				$this->dropColumn('assettransforms', 'mode_temp');
			}
			else
			{
				Craft::log('Tried to clean up `assettransforms` table, but the `mode` column does not exist.', \CLogger::LEVEL_ERROR);
			}
		}
		else
		{
			Craft::log('Tried to clean up `assettransforms` table, but it does not exist.', \CLogger::LEVEL_ERROR);
		}
	}
}
