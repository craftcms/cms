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
	 * @param int          $elementId The ID of the element getting indexed.
	 * @param string       $localeId  The locale ID of the content getting indexed.
	 * @param string|array $keywords  The dirty element keywords.
	 *                                This can be a string (for all non-content keywords)
	 *                                or an array of strings, indexed by the field IDs.
	 * @return bool  Whether the indexing was a success.
	 */
	public function indexElementKeywords($elementId, $localeId, $keywords)
	{
		if (is_string($keywords))
		{
			// Store non-field-specific keywords with a fieldId of '0'
			$keywords = array('0' => $keywords);
		}

		foreach ($keywords as $fieldId => $dirtyKeywords)
		{
			// Clean 'em up
			$cleanKeywords = $this->_normalizeKeywords($dirtyKeywords);

			if ($cleanKeywords)
			{
				// Insert/update the row in searhindex
				$table = DbHelper::addTablePrefix('searchindex');
				$sql = 'INSERT INTO '.craft()->db->quoteTableName($table).' (' .
					craft()->db->quoteColumnName('elementId').', ' .
					craft()->db->quoteColumnName('fieldId').', ' .
					craft()->db->quoteColumnName('locale').', ' .
					craft()->db->quoteColumnName('keywords') .
					') VALUES (:elementId, :fieldId, :locale, :keywords) ' .
					'ON DUPLICATE KEY UPDATE '.craft()->db->quoteColumnName('keywords').' = :keywords';

				craft()->db->createCommand()->setText($sql)->execute(array(
					':elementId' => $elementId,
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
	 * @param mixed  $query      The search query.
	 * @return array The filtered list of element IDs.
	 */
	public function filterElementIdsByQuery($elementIds, $query)
	{
		return array();
	}

	/**
	 * Normalizes search keywords.
	 *
	 * @access private
	 * @param string  $keywords The dirty keywords.
	 * @return string The cleansed keywords.
	 */
	private function _normalizeKeywords($keywords)
	{
		// Convert extended ASCII characters to low ASCII
		$keywords = StringHelper::asciiString($keywords);

		// Normalize to lowercase
		$keywords = strtolower($keywords);

		// ...

		return $keywords;
	}
}
