<?php
namespace Craft;

/**
 * Handles search operations.
 */
class SearchService extends BaseApplicationComponent
{
	/**
	 * Indexes the keywords for a given element and locale.
	 *
	 * @param int    $elementId The ID of the element getting indexed.
	 * @param string $localeId  The locale ID of the content getting indexed.
	 * @param array  $keywords  The element keywords, indexed by attribute name or field ID.
	 * @return bool  Whether the indexing was a success.
	 */
	public function indexElementKeywords($elementId, $localeId, $keywords)
	{
		foreach ($keywords as $attribute => $dirtyKeywords)
		{
			// Is this for a field?
			if (is_int($attribute) || (string) intval($attribute) === (string) $attribute)
			{
				$fieldId = (string) $attribute;
				$attribute = 'field';
			}
			else
			{
				$fieldId = '0';
				$attribute = strtolower($attribute);
			}

			// Clean 'em up
			$cleanKeywords = StringHelper::normalizeKeywords($dirtyKeywords);

			if ($cleanKeywords)
			{
				// Add padding around keywords
				$cleanKeywords = "| {$cleanKeywords} |";

				// Insert/update the row in searchindex
				$table = DbHelper::addTablePrefix('searchindex');
				$sql = 'INSERT INTO '.craft()->db->quoteTableName($table).' (' .
					craft()->db->quoteColumnName('elementId').', ' .
					craft()->db->quoteColumnName('attribute').', ' .
					craft()->db->quoteColumnName('fieldId').', ' .
					craft()->db->quoteColumnName('locale').', ' .
					craft()->db->quoteColumnName('keywords') .
					') VALUES (:elementId, :attribute, :fieldId, :locale, :keywords) ' .
					'ON DUPLICATE KEY UPDATE '.craft()->db->quoteColumnName('keywords').' = :keywords';

				craft()->db->createCommand()->setText($sql)->execute(array(
					':elementId' => $elementId,
					':attribute' => $attribute,
					':fieldId'   => $fieldId,
					':locale'    => $localeId,
					':keywords'  => $cleanKeywords
				));
			}
			else
			{
				// Delete the searchindex row if it exists
				craft()->db->createCommand()->delete('searchindex', array(
					'elementId' => $elementId,
					'attribute' => $attribute,
					'fieldId'   => $fieldId,
					'locale'    => $localeId
				));
			}
		}

		return true;
	}

	/**
	 * Filters a list of element IDs by a given search query.
	 *
	 * @param array  $elementIds The list of element IDs to filter by the search query.
	 * @param mixed  $query      The search query (either a string or a SearhQuery instance)
	 * @return array The filtered list of element IDs.
	 */
	public function filterElementIdsByQuery($elementIds, $query)
	{
		if (is_string($query))
		{
			$query = new SearchQuery($query);
		}

		//$ignore = craft()->config->get('searchIgnoreWords');

		return array();
	}

}
