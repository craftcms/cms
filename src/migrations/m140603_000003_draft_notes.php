<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140603_000003_draft_notes extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->columnExists('entrydrafts', 'notes'))
		{
			$this->addColumnAfter('entrydrafts', 'notes', array('column' => 'tinytext'), 'name');
		}

		return true;
	}
}
