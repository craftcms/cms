<?php
namespace Craft;

/**
 * Handles search operations.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class SearchService extends BaseApplicationComponent
{
	// Constants
	// =========================================================================

	// Reformat this?
	const DEFAULT_STOP_WORDS = "a's able about above according accordingly across actually after afterwards again against ain't all allow allows almost alone along already also although always am among amongst an and another any anybody anyhow anyone anything anyway anyways anywhere apart appear appreciate appropriate are aren't around as aside ask asking associated at available away awfully be became because become becomes becoming been before beforehand behind being believe below beside besides best better between beyond both brief but by c'mon c's came can can't cannot cant cause causes certain certainly changes clearly co com come comes concerning consequently consider considering contain containing contains corresponding could couldn't course currently definitely described despite did didn't different do does doesn't doing don't done down downwards during each edu eg eight either else elsewhere enough entirely especially et etc even ever every everybody everyone everything everywhere ex exactly example except far few fifth first five followed following follows for former formerly forth four from further furthermore get gets getting given gives go goes going gone got gotten greetings had hadn't happens hardly has hasn't have haven't having he he's hello help hence her here here's hereafter hereby herein hereupon hers herself hi him himself his hither hopefully how howbeit however i'd i'll i'm i've ie if ignored immediate in inasmuch inc indeed indicate indicated indicates inner insofar instead into inward is isn't it it'd it'll it's its itself just keep keeps kept know known knows last lately later latter latterly least less lest let let's like liked likely little look looking looks ltd mainly many may maybe me mean meanwhile merely might more moreover most mostly much must my myself name namely nd near nearly necessary need needs neither never nevertheless new next nine no nobody non none noone nor normally not nothing novel now nowhere obviously of off often oh ok okay old on once one ones only onto or other others otherwise ought our ours ourselves out outside over overall own particular particularly per perhaps placed please plus possible presumably probably provides que quite qv rather rd re really reasonably regarding regardless regards relatively respectively right said same saw say saying says second secondly see seeing seem seemed seeming seems seen self selves sensible sent serious seriously seven several shall she should shouldn't since six so some somebody somehow someone something sometime sometimes somewhat somewhere soon sorry specified specify specifying still sub such sup sure t's take taken tell tends th than thank thanks thanx that that's thats the their theirs them themselves then thence there there's thereafter thereby therefore therein theres thereupon these they they'd they'll they're they've think third this thorough thoroughly those though three through throughout thru thus to together too took toward towards tried tries truly try trying twice two un under unfortunately unless unlikely until unto up upon us use used useful uses using usually value various very via viz vs want wants was wasn't way we we'd we'll we're we've welcome well went were weren't what what's whatever when whence whenever where where's whereafter whereas whereby wherein whereupon wherever whether which while whither who who's whoever whole whom whose why will willing wish with within without won't wonder would wouldn't yes yet you you'd you'll you're you've your yours yourself yourselves zero";

	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private static $_ftMinWordLength;

	/**
	 * @var
	 */
	private static $_ftStopWords;

	/**
	 * @var
	 */
	private $_tokens;

	/**
	 * @var
	 */
	private $_terms;

	/**
	 * @var
	 */
	private $_groups;

	// Public Methods
	// =========================================================================

	/**
	 * Indexes the attributes of a given element defined by its element type.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return bool Whether the indexing was a success.
	 */
	public function indexElementAttributes(BaseElementModel $element)
	{
		// Get the element type
		$elementTypeClass = $element->getElementType();
		$elementType = craft()->elements->getElementType($elementTypeClass);

		// Does it have any searchable attributes?
		$searchableAttributes = $elementType->defineSearchableAttributes();

		$searchableAttributes[] = 'slug';

		if ($elementType->hasTitles())
		{
			$searchableAttributes[] = 'title';
		}

		foreach ($searchableAttributes as $attribute)
		{
			$value = $element->$attribute;
			$value = StringHelper::arrayToString($value);
			$this->_indexElementKeywords($element->id, $attribute, '0', $element->locale, $value);
		}

		return true;
	}

	/**
	 * Indexes the field values for a given element and locale.
	 *
	 * @param int    $elementId The ID of the element getting indexed.
	 * @param string $localeId  The locale ID of the content getting indexed.
	 * @param array  $fields    The field values, indexed by field ID.
	 *
	 * @return bool  Whether the indexing was a success.
	 */
	public function indexElementFields($elementId, $localeId, $fields)
	{
		foreach ($fields as $fieldId => $value)
		{
			$this->_indexElementKeywords($elementId, 'field', (string) $fieldId, $localeId, $value);
		}

		return true;
	}

	/**
	 * Filters a list of element IDs by a given search query.
	 *
	 * @param array $elementIds   The list of element IDs to filter by the search query.
	 * @param mixed $query        The search query (either a string or a SearchQuery instance)
	 * @param bool  $scoreResults Whether to order the results based on how closely they match the query.
	 *
	 * @return array The filtered list of element IDs.
	 */
	public function filterElementIdsByQuery($elementIds, $query, $scoreResults = true)
	{
		if (is_string($query))
		{
			$query = new SearchQuery($query);
		}

		// Get tokens for query
		$this->_tokens  = $query->getTokens();
		$this->_terms   = array();
		$this->_groups  = array();

		// Set Terms and Groups based on tokens
		foreach ($this->_tokens as $obj)
		{
			if ($obj instanceof SearchQueryTermGroup)
			{
				$this->_groups[] = $obj->terms;
			}
			else
			{
				$this->_terms[] = $obj;
			}
		}

		// Get where clause from tokens, bail out if no valid query is there
		$where = $this->_getWhereClause();

		if (!$where)
		{
			return array();
		}

		// Begin creating SQL
		$sql = sprintf('SELECT * FROM %s WHERE %s',
			craft()->db->quoteTableName(craft()->db->addTablePrefix('searchindex')),
			$where
		);

		// Append elementIds to QSL
		if ($elementIds)
		{
			$sql .= sprintf(' AND %s IN (%s)',
				craft()->db->quoteColumnName('elementId'),
				implode(',', $elementIds)
			);
		}

		// Execute the sql
		$results = craft()->db->createCommand()->setText($sql)->queryAll();

		// Are we scoring the results?
		if ($scoreResults)
		{
			$scoresByElementId = array();

			// Loop through results and calculate score per element
			foreach ($results as $row)
			{
				$elementId = $row['elementId'];
				$score = $this->_scoreRow($row);

				if (!isset($scoresByElementId[$elementId]))
				{
					$scoresByElementId[$elementId] = $score;
				}
				else
				{
					$scoresByElementId[$elementId] += $score;
				}
			}

			// Sort found elementIds by score
			arsort($scoresByElementId);

			// Store entry ids in return value
			$elementIds = array_keys($scoresByElementId);
		}
		else
		{
			// Don't apply score, just return the IDs
			$elementIds = array();

			foreach ($results as $row)
			{
				$elementIds[] = $row['elementId'];
			}

			$elementIds = array_unique($elementIds);
		}

		// Return elementIds
		return $elementIds;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the FULLTEXT minimum word length.
	 *
	 * @todo Get actual value from DB
	 * @return int
	 */
	private static function _getMinWordLength()
	{
		if (!isset(static::$_ftMinWordLength))
		{
			static::$_ftMinWordLength = 4;
		}

		return static::$_ftMinWordLength;
	}

	/**
	 * Returns the FULLTEXT stop words.
	 *
	 * @todo Make this customizable from the config settings
	 * @return array
	 */
	private static function _getStopWords()
	{
		if (!isset(static::$_ftStopWords))
		{
			$words = explode(' ', static::DEFAULT_STOP_WORDS);

			foreach ($words as &$word)
			{
				$word = StringHelper::normalizeKeywords($word);
			}

			static::$_ftStopWords = $words;
		}

		return static::$_ftStopWords;
	}

	/**
	 * Indexes keywords for a specific element attribute/field.
	 *
	 * @param int         $elementId
	 * @param string      $attribute
	 * @param string      $fieldId
	 * @param string|null $localeId
	 * @param string      $dirtyKeywords
	 *
	 * @return null
	 */
	private function _indexElementKeywords($elementId, $attribute, $fieldId, $localeId, $dirtyKeywords)
	{
		$attribute = StringHelper::toLowerCase($attribute);

		if (!$localeId)
		{
			$localeId = craft()->i18n->getPrimarySiteLocaleId();
		}

		// Clean 'em up
		$cleanKeywords = StringHelper::normalizeKeywords($dirtyKeywords);

		// Save 'em
		$keyColumns = array(
			'elementId' => $elementId,
			'attribute' => $attribute,
			'fieldId'   => $fieldId,
			'locale'    => $localeId
		);

		if ($cleanKeywords !== null && $cleanKeywords !== false && $cleanKeywords !== '')
		{
			// Add padding around keywords
			$cleanKeywords = ' '.$cleanKeywords.' ';
		}

		$cleanKeywordsLength = strlen($cleanKeywords);

		$maxDbColumnSize = DbHelper::getTextualColumnStorageCapacity(ColumnType::Text);

		// Give ourselves 10% wiggle room.
		$maxDbColumnSize = ceil($maxDbColumnSize * 0.9);

		if ($cleanKeywordsLength > $maxDbColumnSize)
		{
			// Time to truncate.
			$cleanKeywords = mb_strcut($cleanKeywords, 0, $maxDbColumnSize);

			// Make sure we don't cut off a word in the middle.
			if ($cleanKeywords[mb_strlen($cleanKeywords) - 1] !== ' ')
			{
				$position = mb_strrpos($cleanKeywords, ' ');

				if ($position)
				{
					$cleanKeywords = mb_substr($cleanKeywords, 0, $position + 1);
				}
			}

		}

		// Insert/update the row in searchindex
		craft()->db->createCommand()->insertOrUpdate('searchindex', $keyColumns, array(
			'keywords' => $cleanKeywords
		), false);
	}

	/**
	 * Calculate score for a result.
	 *
	 * @param array $row A single result from the search query.
	 *
	 * @return float The total score for this row.
	 */
	private function _scoreRow($row)
	{
		// Starting point
		$score = 0;

		// Loop through AND-terms and score each one against this row
		foreach ($this->_terms as $term)
		{
			$score += $this->_scoreTerm($term, $row);
		}

		// Loop through each group of OR-terms
		foreach ($this->_groups as $terms)
		{
			// OR-terms are weighted less depending on the amount of OR terms in the group
			$weight = 1 / count($terms);

			// Get the score for each term and add it to the total
			foreach ($terms as $term)
			{
				$score += $this->_scoreTerm($term, $row, $weight);
			}
		}

		return $score;
	}

	/**
	 * Calculate score for a row/term combination.
	 *
	 * @param  object    $term   The SearchQueryTerm to score.
	 * @param  array     $row    The result row to score against.
	 * @param  float|int $weight Optional weight for this term.
	 *
	 * @return float The total score for this term/row combination.
	 */
	private function _scoreTerm($term, $row, $weight = 1)
	{
		// Skip these terms: locale and exact filtering is just that, no weighted search applies since all elements will
		// already apply for these filters.
		if (
			$term->attribute == 'locale' ||
			$term->exact ||
			!($keywords = $this->_normalizeTerm($term->term))
		)
		{
			return 0;
		}

		// Account for substrings
		if ($term->subLeft)
		{
			$keywords = $keywords.' ';
		}

		if ($term->subRight)
		{
			$keywords = ' '.$keywords;
		}

		// Get haystack and safe word count
		$haystack  = $this->_removePadding($row['keywords'], true);
		$wordCount = count(array_filter(explode(' ', $haystack)));

		// Get number of matches
		$score = mb_substr_count($haystack, $keywords);

		if ($score)
		{
			// Exact match
			if (trim($keywords) == trim($haystack))
			{
				$mod = 100;
			}
			// Don't scale up for substring matches
			else if ($term->subLeft || $term->subRight)
			{
				$mod = 10;
			}
			else
			{
				$mod = 50;
			}

			// If this is a title, 5X it
			if ($row['attribute'] == 'title')
			{
				$mod *= 5;
			}

			$score = ($score / $wordCount) * $mod * $weight;
		}

		return $score;
	}

	/**
	 * Get the complete where clause for current tokens
	 *
	 * @return string|false
	 */
	private function _getWhereClause()
	{
		$where  = array();

		// Add the regular terms to the WHERE clause
		if ($this->_terms)
		{
			$condition = $this->_processTokens($this->_terms);

			if ($condition === false)
			{
				return false;
			}

			$where[] = $condition;
		}

		// Add each group to the where clause
		foreach ($this->_groups as $group)
		{
			$condition = $this->_processTokens($group, false);

			if ($condition === false)
			{
				return false;
			}

			$where[] = $condition;
		}

		// And combine everything with AND
		return implode(' AND ', $where);
	}

	/**
	 * Generates partial WHERE clause for search from given tokens
	 *
	 * @param array $tokens
	 * @param bool  $inclusive
	 *
	 * @return string|false
	 */
	private function _processTokens($tokens = array(), $inclusive = true)
	{
		$andor = $inclusive ? ' AND ' : ' OR ';
		$where = array();
		$words = array();

		foreach ($tokens as $obj)
		{
			// Get SQL and/or keywords
			list($sql, $keywords) = $this->_getSqlFromTerm($obj);

			if ($sql === false && $inclusive)
			{
				return false;
			}

			// If we have SQL, just add that
			if ($sql)
			{
				$where[] = $sql;
			}
			// No SQL but keywords, save them for later
			else if ($keywords)
			{
				if ($inclusive)
				{
					$keywords = '+'.$keywords;
				}

				$words[] = $keywords;
			}
		}

		// If we collected full-text words, combine them into one
		if ($words)
		{
			$where[] = $this->_sqlMatch($words);
		}

		// If we have valid where clauses now, stringify them
		if (!empty($where))
		{
			// Implode WHERE clause to a string
			$where = implode($andor, $where);

			// And group together for non-inclusive queries
			if (!$inclusive)
			{
				$where = "({$where})";
			}
		}
		else
		{
			// If the tokens didn't produce a valid where clause,
			// make sure we return false
			$where = false;
		}

		return $where;
	}

	/**
	 * Generates a piece of WHERE clause for fallback (LIKE) search from search term
	 * or returns keywords to use in a MATCH AGAINST clause
	 *
	 * @param  SearchQueryTerm $term
	 *
	 * @return array
	 */
	private function _getSqlFromTerm(SearchQueryTerm $term)
	{
		// Initiate return value
		$sql = null;
		$keywords = null;

		// Check for locale first
		if ($term->attribute == 'locale')
		{
			$oper = $term->exclude ? '!=' : '=';
			return array($this->_sqlWhere($term->attribute, $oper, $term->term), $keywords);
		}

		// Check for other attributes
		if (!is_null($term->attribute))
		{
			// Is attribute a valid fieldId?
			$fieldId = $this->_getFieldIdFromAttribute($term->attribute);

			if ($fieldId)
			{
				$attr = 'fieldId';
				$val  = $fieldId;
			}
			else
			{
				$attr = 'attribute';
				$val  = $term->attribute;
			}

			// Use subselect for attributes
			$subSelect = $this->_sqlWhere($attr, '=', $val);
		}
		else
		{
			$subSelect = null;
		}

		// Sanitize term
		if ($term->term !== null)
		{
			$keywords = $this->_normalizeTerm($term->term);

			// Make sure that it didn't result in an empty string (e.g. if they entered '&')
			// unless it's meant to search for *anything* (e.g. if they entered 'attribute:*').
			if ($keywords !== '' || $term->subLeft)
			{
				// Create fulltext clause from term
				if ($keywords !== '' && $this->_isFulltextTerm($keywords) && !$term->subLeft && !$term->exact && !$term->exclude)
				{
					if ($term->subRight)
					{
						$keywords .= '*';
					}

					// Add quotes for exact match
					if (mb_strpos($keywords, ' ') != false)
					{
						$keywords = '"'.$keywords.'"';
					}

					// Determine prefix for the full-text keyword
					if ($term->exclude)
					{
						$keywords = '-'.$keywords;
					}

					// Only create an SQL clause if there's a subselect. Otherwise, return the keywords.
					if ($subSelect)
					{
						// If there is a subselect, create the MATCH AGAINST bit
						$sql = $this->_sqlMatch($keywords);
					}
				}

				// Create LIKE clause from term
				else
				{
					if ($term->exact)
					{
						// Create exact clause from term
						$operator = $term->exclude ? 'NOT LIKE' : 'LIKE';
						$keywords = ($term->subLeft ? '%' : ' ').$keywords.($term->subRight ? '%' : ' ');
					}
					else
					{
						// Create LIKE clause from term
						$operator = $term->exclude ? 'NOT LIKE' : 'LIKE';
						$keywords = ($term->subLeft ? '%' : '% ').$keywords.($term->subRight ? '%' : ' %');
					}

					// Generate the SQL
					$sql = $this->_sqlWhere('keywords', $operator, $keywords);
				}
			}
		}
		else
		{
			// Support for attribute:* syntax to just check if something has *any* keyword value.
			if ($term->subLeft)
			{
				$sql = $this->_sqlWhere('keywords', '!=', '');
			}
		}

		// If we have a where clause in the subselect, add the keyword bit to it.
		if ($subSelect && $sql)
		{
			$sql = $this->_sqlSubSelect($subSelect.' AND '.$sql);

			// We need to reset keywords even if the subselect ended up in no results.
			$keywords = null;
		}

		return array($sql, $keywords);
	}

	/**
	 * Normalize term from tokens, keep a record for cache.
	 *
	 * @param string $term
	 *
	 * @return string
	 */
	private function _normalizeTerm($term)
	{
		static $terms = array();

		if (!array_key_exists($term, $terms))
		{
			$terms[$term] = StringHelper::normalizeKeywords($term);
		}

		return $terms[$term];
	}

	/**
	 * Remove padding from keywords. Might seem silly now, but padding might change.
	 *
	 * @param string $keywords
	 *
	 * @return string
	 */
	private function _removePadding($keywords)
	{
		return trim($keywords);
	}

	/**
	 * Determine if search term is eligible for full-text or not.
	 *
	 * @param string $term The search term to check
	 *
	 * @return bool
	 */
	private function _isFulltextTerm($term)
	{
		$ftStopWords = static::_getStopWords();

		// Check if complete term is in stopwords
		if (in_array($term, $ftStopWords)) return false;

		// Split the term into individual words
		$words = explode(' ', $term);

		// Then loop through terms and return false it doesn't match up
		foreach ($words as $word)
		{
			if (mb_strlen($word) < static::_getMinWordLength() || in_array($word, $ftStopWords))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the fieldId for given attribute or 0 for unmatched.
	 *
	 * @param string $attribute
	 *
	 * @return int
	 */
	private function _getFieldIdFromAttribute($attribute)
	{
		// Get field id from service
		$field = craft()->fields->getFieldByHandle($attribute);

		// Fallback to 0
		return ($field) ? $field->id : 0;
	}

	/**
	 * Get SQL bit for simple WHERE clause
	 *
	 * @param string $key  The attribute.
	 * @param string $oper The operator.
	 * @param string $val  The value.
	 *
	 * @return string
	 */
	private function _sqlWhere($key, $oper, $val)
	{
		return sprintf("(%s %s '%s')",
			craft()->db->quoteColumnName($key),
			$oper,
			$val
		);
	}

	/**
	 * Get SQL but for MATCH AGAINST clause.
	 *
	 * @param mixed  $val  String or Array of keywords
	 * @param bool   $bool Use In Boolean Mode or not
	 *
	 * @return string
	 */
	private function _sqlMatch($val, $bool = true)
	{
		return sprintf("MATCH(%s) AGAINST('%s'%s)",
			craft()->db->quoteColumnName('keywords'),
			(is_array($val) ? implode(' ', $val) : $val),
			($bool ? ' IN BOOLEAN MODE' : '')
		);
	}

	/**
	 * Get SQL bit for sub-selects.
	 *
	 * @param string $where
	 *
	 * @return string|false
	 */
	private function _sqlSubSelect($where)
	{
		// FULLTEXT indexes are not used in queries with subselects, so let's do this as its own query.
		$elementIds = craft()->db->createCommand()
			->select('elementId')
			->from('searchindex')
			->where($where)
			->queryColumn();

		if ($elementIds)
		{
			return craft()->db->quoteColumnName('elementId').' IN ('.implode(', ', $elementIds).')';
		}
		else
		{
			return false;
		}
	}
}
