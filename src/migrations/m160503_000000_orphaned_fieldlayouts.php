<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160503_000000_orphaned_fieldlayouts extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Grab all entrytype fieldlayout ids.
		$entryTypeFieldLayoutIds = craft()->db->createCommand()
			->select('fieldLayoutId')
			->from('entrytypes')
			->queryColumn();

		if ($entryTypeFieldLayoutIds)
		{
			// Find any orphaned ones in the fieldlayouts table.
			$fieldLayoutIds = craft()->db->createCommand()
				->select('id')
				->from('fieldlayouts')
				->where(array('not in', 'id', $entryTypeFieldLayoutIds))
				->andWhere('type="Entry"')
				->queryColumn();

			// Nuke em.
			if ($fieldLayoutIds)
			{
				Craft::log('Found and deleting '.count($fieldLayoutIds).' orphaned entrytype field layouts. ids: '.implode(', ', $fieldLayoutIds), LogLevel::Info, true);
				$this->delete('fieldlayouts', array('in', 'id', $fieldLayoutIds));
			}
		}

		return true;
	}
}
