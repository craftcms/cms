<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140815_000001_add_format_to_transforms extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{

		// Allow transforms to have a format
		$this->addColumnAfter('assettransforms', 'format', array(ColumnType::Varchar, 'required' => false), 'width');

		return true;
	}
}
