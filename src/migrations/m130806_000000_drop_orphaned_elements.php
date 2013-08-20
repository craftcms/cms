<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130806_000000_drop_orphaned_elements extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Get all of the non-orphaned asset files, tags and entry ids from their respective tables.
		$allIds = array_merge(
			$this->_getIdsInTable('assetfiles'),
			$this->_getIdsInTable('tags'),
			$this->_getIdsInTable('entries'),
			$this->_getIdsInTable('users')
		);

		if ($allIds)
		{
			$orphans = craft()->db->createCommand()
				->select('*')
				->from('elements')
				->where(array('and',
							array('in', 'type', array('Entry', 'Asset', 'Tag')),
							array('not in', 'id', $allIds)))
				->queryAll();

			$orphans = array_filter($orphans);

			// Log
			if ($orphans && count($orphans) > 0)
			{
				Craft::log('Found '.count($orphans).' orphaned rows in the `elements` table.', LogLevel::Info, true);
				foreach ($orphans as $orphan)
				{
					Craft::log('Orphaned element - id: '.$orphan['id'].'.', LogLevel::Info, true);
				}

				Craft::log('Murdering orphans...', LogLevel::Info, true);

				// Get rid of them in the relations table, first.
				$this->delete('relations', array('or',
					array('not in', 'parentId', $allIds),
					array('not in', 'childId', $allIds)
				));

				// Now delete them from elements.
				$this->delete('elements', array('and',
					array('in', 'type', array('Entry', 'Asset', 'Tag')),
					array('not in', 'id', $allIds)
				));

				Craft::log('I did it.  They are all dead.', LogLevel::Info, true);
			}
			else
			{
				Craft::log('No orphans to murder today.', LogLevel::Info, true);
			}
		}

		return true;
	}

	/**
	 * Returns all of the field layout IDs in a given table.
	 *
	 * @access private
	 * @param string $table
	 * @return array
	 */
	private function _getIdsInTable($table)
	{
		$ids = craft()->db->createCommand()
			->select('id')
			->from($table)
			->queryColumn();

		return array_filter($ids);
	}
}
