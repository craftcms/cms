<?php
namespace Blocks;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m121120_143306_section_title_labels extends DbMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			// Add the titleLabel column to blx_sections
			$config = array('column' => ColumnType::Varchar, 'null' => false, 'default' => 'Title');
			blx()->db->createCommand()->addColumnAfter('sections', 'titleLabel', $config, 'handle');
		}

		return true;
	}
}
