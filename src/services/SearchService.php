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
			}

			// Clean 'em up
			$cleanKeywords = $this->_normalizeKeywords($dirtyKeywords);

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
	 * @param mixed  $query      The search query.
	 * @return array The filtered list of element IDs.
	 */
	public function filterElementIdsByQuery($elementIds, $query)
	{
		//$ignore = craft()->config->get('searchIgnoreWords');

		return array();
	}

	/**
	 * Normalizes search keywords.
	 *
	 * @access private
	 * @param string  $keywords The dirty keywords.
	 * @return string The cleansed keywords.
	 */
	private function _normalizeKeywords($str)
	{
		// Flatten
		if (is_array($str)) $str = StringHelper::arrayToString($str, ' ');

		// Get rid of tags
		$str = strip_tags($str);

		// Convert non-breaking spaces entities to regular ones
		$str = str_replace(array('&nbsp;', '&#160;', '&#xa0;') , ' ', $str);

		// Get rid of entities
		$str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');

		// Remove punctuation and diacritics
		$str = strtr($str, $this->_getCharMap());

		// Normalize to lowercase
		$str = function_exists('mb_strtolower') ? mb_strtolower($str, 'UTF-8') : strtolower($str);

		// Remove ignore-words?
		// ...

		// Strip out new lines and superfluous spaces
		$str = preg_replace('/[\n\r]+/', ' ', $str);
		$str = preg_replace('/\s{2,}/', ' ', $str);

		// Trim white space
		$str = trim($str);

		return $str;
	}

	/**
	 * Get array of chars to be used for conversion.
	 *
	 * @access private
	 * @return array
	 */
	private function _getCharMap()
	{
		// Keep local copy
		static $map = array();

		if (empty($map))
		{
			// This will replace accented chars with non-accented chars
			foreach (StringHelper::getAsciiCharMap() AS $k => $v)
			{
				$map[StringHelper::chr($k)] = $v;
			}

			// Replace punctuation with a space
			foreach (StringHelper::getAsciiPunctuation() AS $i)
			{
				$map[StringHelper::chr($i)] = ' ';
			}
		}

		// Return the char map
		return $map;
	}

}
