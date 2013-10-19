<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m131105_000001_element_content extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Find all of the elements that don't have a content row yet
		$elementIds = craft()->db->createCommand()
			->select('elements.id')
			->from('elements elements')
			->leftJoin('content content', 'content.elementId = elements.id')
			->where('content.id IS NULL')
			->queryColumn();

		if ($elementIds)
		{
			$rows = array();
			$locale = craft()->i18n->getPrimarySiteLocaleId();

			foreach ($elementIds as $elementId)
			{
				$rows[] = array($elementId, $locale);
			}

			craft()->db->createCommand()->insertAll('content', array('elementId', 'locale'), $rows);
		}

		return true;
	}
}
