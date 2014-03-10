<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_100013_allow_temp_source_transforms extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// No need to torture the Dev track guys again
		if (craft()->migrations->hasRun('m140401_100013_allow_temp_source_transforms'))
		{
			return true;
		}

		$this->alterColumn('assettransformindex', 'sourceId', array('column' => ColumnType::Int, 'required' => false));

		return true;
	}
}
