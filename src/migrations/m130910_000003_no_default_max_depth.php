<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130910_000003_no_default_max_depth extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Remove the default value from the sections.maxDepth column
		$this->alterColumn('sections', 'maxDepth', array('column' => ColumnType::Int, 'maxLength' => 11, 'decimals' => 0, 'unsigned' => true, 'length' => 10));

		// Now deal with any existing non-structure sections
		$this->update('sections', array('maxDepth' => null), array('type' != SectionType::Structure));

		return true;
	}
}
