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
				// Add padding around keywords
				$cleanKeywords = "| {$cleanKeywords} |";

				// Insert/update the row in searchindex
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
		$str = function_exists('mb_strtolower') ? mb_strtolower($str) : strtolower($str);

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

			// Punctuation
			$chars = array(
				33, 34, 35, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 58, 59, 60, 62, 63,
				64, 91, 92, 93, 94, 123, 124, 125, 126, 161, 162, 163, 164, 165, 166,
				167, 168, 169, 170, 171, 172, 174, 175, 176, 177, 178, 179, 180, 181,
				182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 215, 402, 710, 732,
				8211, 8212, 8213, 8216, 8217, 8218, 8220, 8221, 8222, 8224, 8225, 8226,
				8227, 8230, 8240, 8242, 8243, 8249, 8250, 8252, 8254, 8260, 8364, 8482,
				8592, 8593, 8594, 8595, 8596, 8629, 8656, 8657, 8658, 8659, 8660, 8704,
				8706, 8707, 8709, 8711, 8712, 8713, 8715, 8719, 8721, 8722, 8727, 8730,
				8733, 8734, 8736, 8743, 8744, 8745, 8746, 8747, 8756, 8764, 8773, 8776,
				8800, 8801, 8804, 8805, 8834, 8835, 8836, 8838, 8839, 8853, 8855, 8869,
				8901, 8968, 8969, 8970, 8971, 9001, 9002, 9674, 9824, 9827, 9829, 9830
			);

			// Replace punctuation with a space
			foreach ($chars AS $i)
			{
				$map[StringHelper::chr($i)] = ' ';
			}
		}

		// Return the char map
		return $map;
	}

}
