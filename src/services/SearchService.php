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
	 * @param array  $elementIds The list of element IDs to filter by the search term.
	 * @param string $term       The search term.
	 * @param mixed  $fieldIds   A list of field IDs to search, or null to search everywhere.
	 * @param string $mode       The search mode ("any", "all", or "exact"). Defaults to "all".
	 * @return array The filtered list of element IDs.
	 */
	public function filterElementIdsBySearchTerm($elementIds, $term, $fieldIds = null, $mode = SearchMode::All)
	{
		return array();
	}
}
