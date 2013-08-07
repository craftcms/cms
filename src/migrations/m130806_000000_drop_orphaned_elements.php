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
			$this->_getIdsInTable('entries')
		);

		if ($allIds)
		{
			$this->delete('elements', array('and',
				array('in', 'type', array('Entry', 'Asset', 'Tag')),
				array('not in', 'id', $allIds)
			));
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
