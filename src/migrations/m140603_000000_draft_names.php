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
			->select('id, entryId, locale')
			->from('entrydrafts')
			->where(array('or', 'name is null', 'name = ""'))
			->order('dateCreated asc')
			->queryAll();

		$draftCountsByEntryIdAndLocale = array();

		foreach ($drafts as $draft)
		{
			$entryId = $draft['entryId'];
			$locale  = $draft['locale'];

			if (!isset($draftCountsByEntryIdAndLocale[$entryId][$locale]))
			{
				$draftCountsByEntryIdAndLocale[$entryId][$locale] = 1;
			}
			else
			{
				$draftCountsByEntryIdAndLocale[$entryId][$locale]++;
			}

			$this->update('entrydrafts', array(
				'name' => 'Draft '.$draftCountsByEntryIdAndLocale[$entryId][$locale]
			), array(
				'id' => $draft['id'])
			);
		}

		// Make the name column required
		$this->alterColumn('entrydrafts', 'name',  array('column' => ColumnType::Varchar, 'null' => false));

		return true;
	}
}
