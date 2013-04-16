<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130416_000001_add_crop_position extends BaseMigration
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
			if (($positionColumn = $assetTransformsTable->getColumn('position')) == null)
			{
				Craft::log('Adding the `position` column to `assettransforms`', LogLevel::Info, true);
				$this->addColumnAfter('assettransforms', 'position', array(AttributeType::Enum, 'values' => array('top-left', 'top-center', 'top-right', 'center-left', 'center-center', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right'), 'required' => true, 'default' => 'center-center'), 'mode');
				Craft::log('Added the `position` column to `assettransforms`', LogLevel::Info, true);
			}
			else
			{
				Craft::log('Tried to add the `position` column to `assettransforms`, but the column already exists.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Tried to add the `position` column to `assettransforms`, but the table does not exist!', LogLevel::Error);
		}

		return true;
	}
}
