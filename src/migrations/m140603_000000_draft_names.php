<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140603_000000_draft_names extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Find all of the drafts that don't have a name yet, and give them names
		$drafts = craft()->db->createCommand()
			->select('id, entryId')
			->from('entrydrafts')
			->where(array('or', 'name is null', 'name = ""'))
			->order('dateCreated asc')
			->queryAll();

		$draftCountsByEntryId = array();

		foreach ($drafts as $draft)
		{
			if (!isset($draftCountsByEntryId[$draft['entryId']]))
			{
				$draftCountsByEntryId[$draft['entryId']] = 1;
			}
			else
			{
				$draftCountsByEntryId[$draft['entryId']]++;
			}

			$this->update('entrydrafts', array(
				'name' => 'Draft '.$draftCountsByEntryId[$draft['entryId']]
			), array(
				'id' => $draft['id'])
			);
		}

		// Make the name column required
		$this->alterColumn('entrydrafts', 'name',  array('column' => ColumnType::Varchar, 'null' => false));

		return true;
	}
}
