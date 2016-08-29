<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160829_000000_pending_user_content_cleanup extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Find any orphaned entries.
		$ids = craft()->db->createCommand()
			->select('el.id')
			->from('elements el')
			->leftJoin('entries en', 'en.id = el.id')
			->where(
				array('and', 'el.type = :type', 'en.id is null'),
				array(':type' => ElementType::Entry)
			)->queryColumn();

		if ($ids)
		{
			Craft::log('Found '.count($ids).' orphaned element IDs in the elements table: '.implode(', ', $ids), LogLevel::Info, true);

			// Delete 'em
			$this->delete('elements', array('in', 'id', $ids));

			Craft::log('They have been murdered.', LogLevel::Info, true);
		}
		else
		{
			Craft::log('All good here.', LogLevel::Info, true);
		}

		return true;
	}
}
