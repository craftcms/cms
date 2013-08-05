<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130805_193351_drop_orphaned_field_layouts extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Get all of the non-orphaned section/global set/tag set field layout IDs
		$fieldLayoutIds = array_merge(
			$this->_getFieldLayoutIdsInTable('sections'),
			$this->_getFieldLayoutIdsInTable('globalsets'),
			$this->_getFieldLayoutIdsInTable('tagsets')
		);

		if ($fieldLayoutIds)
		{
			$this->delete('fieldlayouts', array('and',
				array('in', 'type', array('Entry', 'GlobalSet', 'Tag')),
				array('not in', 'id', $fieldLayoutIds)
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
	private function _getFieldLayoutIdsInTable($table)
	{
		$fieldLayoutIds = craft()->db->createCommand()
			->select('fieldLayoutId')
			->from($table)
			->where('fieldLayoutId is not null')
			->queryColumn();

		return array_filter($fieldLayoutIds);
	}
}
