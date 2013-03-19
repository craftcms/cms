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
	 * @param array  $keywords  The dirty element keywords, indexed by field ID.
	 * @return bool  Whether the indexing was a success.
	 */
	public function indexElementKeywords($elementId, $localeId, $keywords)
	{
		return true;
	}

	/**
	 * Filters a list of element IDs by a given search term and mode.
	 *
	 * @param array       $elementIds The list of element IDs to filter by the search term.
	 * @param string      $query      The search query.
	 * @param int|null    $fieldId    The field ID to search within, if any.
	 * @param string|null $locale     The locale ID to search within, if any.
	 * @return array      The filtered list of element IDs.
	 */
	public function filterElementIdsByQuery($elementIds, $query, $fieldId = null, $locale = null)
	{
		return array();
	}
}
