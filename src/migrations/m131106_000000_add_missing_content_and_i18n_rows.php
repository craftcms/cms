<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m131106_000000_add_missing_content_and_i18n_rows extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->_addMissingRows('content');
		$this->_addMissingRows('elements_i18n');

		return true;
	}

	private function _addMissingRows($table)
	{
		// Find all of the elements that don't have a row in this table yet
		$elementIds = craft()->db->createCommand()
			->select('elements.id')
			->from('elements elements')
			->leftJoin($table.' '.$table, $table.'.elementId = elements.id')
			->where($table.'.id IS NULL')
			->queryColumn();

		if ($elementIds)
		{
			$rows = array();
			$locale = craft()->i18n->getPrimarySiteLocaleId();

			foreach ($elementIds as $elementId)
			{
				$rows[] = array($elementId, $locale);
			}

			craft()->db->createCommand()->insertAll($table, array('elementId', 'locale'), $rows);
		}
	}
}
