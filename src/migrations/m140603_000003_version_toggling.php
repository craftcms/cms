<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140603_000003_version_toggling extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->columnExists('sections', 'enableVersioning'))
		{
			// Add the `enableVersioning` column to the sections table
			$this->addColumnAfter('sections', 'enableVersioning', array('column' => 'tinyint', 'unsigned' => true, 'maxLength' => 1, 'null' => false, 'default' => false), 'template');

			if (craft()->getEdition() >= Craft::Client)
			{
				// Enable it for all existing sections
				$this->update('sections', array('enableVersioning' => 1));
			}
		}

		return true;
	}
}
