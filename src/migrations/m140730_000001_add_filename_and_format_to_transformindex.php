<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140730_000001_add_filename_and_format_to_transformindex extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{

		// Switching to new format, yay!
		$this->addColumnAfter('assettransformindex', 'filename', array(ColumnType::Varchar, 'required' => false), 'fileId');
		$this->addColumnAfter('assettransformindex', 'format', array(ColumnType::Varchar, 'required' => false), 'filename');

		return true;
	}
}
