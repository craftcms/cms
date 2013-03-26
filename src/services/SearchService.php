<?php
namespace Craft;

/**
 * Handles search operations.
 */
class SearchService extends BaseApplicationComponent
{
	// Reformat this?
	const DEFAULT_STOP_WORDS = "a's able about above according accordingly across actually after afterwards again against ain't all allow allows almost alone along already also although always am among amongst an and another any anybody anyhow anyone anything anyway anyways anywhere apart appear appreciate appropriate are aren't around as aside ask asking associated at available away awfully be became because become becomes becoming been before beforehand behind being believe below beside besides best better between beyond both brief but by c'mon c's came can can't cannot cant cause causes certain certainly changes clearly co com come comes concerning consequently consider considering contain containing contains corresponding could couldn't course currently definitely described despite did didn't different do does doesn't doing don't done down downwards during each edu eg eight either else elsewhere enough entirely especially et etc even ever every everybody everyone everything everywhere ex exactly example except far few fifth first five followed following follows for former formerly forth four from further furthermore get gets getting given gives go goes going gone got gotten greetings had hadn't happens hardly has hasn't have haven't having he he's hello help hence her here here's hereafter hereby herein hereupon hers herself hi him himself his hither hopefully how howbeit however i'd i'll i'm i've ie if ignored immediate in inasmuch inc indeed indicate indicated indicates inner insofar instead into inward is isn't it it'd it'll it's its itself just keep keeps kept know known knows last lately later latter latterly least less lest let let's like liked likely little look looking looks ltd mainly many may maybe me mean meanwhile merely might more moreover most mostly much must my myself name namely nd near nearly necessary need needs neither never nevertheless new next nine no nobody non none noone nor normally not nothing novel now nowhere obviously of off often oh ok okay old on once one ones only onto or other others otherwise ought our ours ourselves out outside over overall own particular particularly per perhaps placed please plus possible presumably probably provides que quite qv rather rd re really reasonably regarding regardless regards relatively respectively right said same saw say saying says second secondly see seeing seem seemed seeming seems seen self selves sensible sent serious seriously seven several shall she should shouldn't since six so some somebody somehow someone something sometime sometimes somewhat somewhere soon sorry specified specify specifying still sub such sup sure t's take taken tell tends th than thank thanks thanx that that's thats the their theirs them themselves then thence there there's thereafter thereby therefore therein theres thereupon these they they'd they'll they're they've think third this thorough thoroughly those though three through throughout thru thus to together too took toward towards tried tries truly try trying twice two un under unfortunately unless unlikely until unto up upon us use used useful uses using usually value various very via viz vs want wants was wasn't way we we'd we'll we're we've welcome well went were weren't what what's whatever when whence whenever where where's whereafter whereas whereby wherein whereupon wherever whether which while whither who who's whoever whole whom whose why will willing wish with within without won't wonder would wouldn't yes yet you you'd you'll you're you've your yours yourself yourselves zero";

	private static $_ftMinWordLength;
	private static $_ftStopWords;

	private $_tokens;
	private $_ftKeywords;

	/**
	 * Returns the FULLTEXT minimum word length.
	 *
	 * @static
	 * @access private
	 * @return int
	 * @todo Get actual value from DB
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
	 * @static
	 * @access private
	 * @return array
	 * @todo Make this customizable from the config settings
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
	 * Indexes the keywords for a given element and locale.
	 *
	 * @param int    $elementId The ID of the element getting indexed.
	 * @param string $localeId  The locale ID of the content getting indexed.
	 * @param array  $keywords  The element keywords, indexed by attribute name or field ID.
	 * @return bool  Whether the indexing was a success.
	 */
	public function indexElementKeywords($elementId, $localeId, $keywords)
	{
		// $sql = array(
		// 	$this->filterElementIdsByQuery(array(), 'salty dog'),
		// 	$this->filterElementIdsByQuery(array(), 'salty dogs'),
		// 	$this->filterElementIdsByQuery(array(), 'salty OR dog'),
		// 	$this->filterElementIdsByQuery(array(), 'salty OR dogs'),
		// 	$this->filterElementIdsByQuery(array(), 'title:salty OR body:dog'),
		// 	$this->filterElementIdsByQuery(array(), '"salty dog"'),
		// 	$this->filterElementIdsByQuery(array(), 'salty -dog'),
		// 	$this->filterElementIdsByQuery(array(), 'salty locale:en_us'),
		// );

		// die('<pre>'.htmlspecialchars(print_r($sql, true)).'</pre>');

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
	 * @param mixed  $query      The search query (either a string or a SearchQuery instance)
	 * @return array The filtered list of element IDs.
	 */
	public function filterElementIdsByQuery($elementIds, $query)
	{
		if (is_string($query))
		{
			$query = new SearchQuery($query);
		}

		// Get tokens for query
		$this->_tokens = $query->getTokens();
		$this->_ftKeywords = array();

		// Initiate WHERE array
		$where = array();

		// Get the where clause from the tokens
		if ($sql = $this->_getWhereClause())
		{
			$where[] = $sql;
		}

		// If getting the where clause resulted in full-text keywords,
		// add those to the where clause
		if ($this->_ftKeywords)
		{
			$where[] = $this->_sqlMatch(implode(' ', $this->_ftKeywords));
		}

		// Execute query
		if ($where)
		{
			$table = DbHelper::addTablePrefix('searchindex');
			$sql = 'SELECT '.craft()->db->quoteColumnName('elementId').' '.
				'FROM '.craft()->db->quoteTableName($table).' '.
				'WHERE '.implode(' AND ', $where);

			return $sql;
		}

		return array();
	}

	/**
	 * Generates complete WHERE clause for search from given tokens.
	 *
	 * @access private
	 * @param array $tokens
	 * @param string $glue
	 */
	private function _getWhereClause($tokens = array(), $glue = 'AND')
	{
		if (!$tokens)
		{
			$tokens = $this->_tokens;
		}

		$where = array();

		foreach ($tokens AS $obj)
		{
			if ($obj instanceof SearchQueryTermGroup && ($sql = $this->_getWhereClause($obj->terms, 'OR')))
			{
				$where[] = '('.$sql.')';
			}
			else if ($obj instanceof SearchQueryTerm && ($sql = $this->_getSqlFromTerm($obj, $glue)))
			{
				$where[] = $sql;
			}
		}

		return implode(" {$glue} ", $where);
	}

	/**
	 * Generates a piece of WHERE clause for fallback (LIKE) search from search term
	 *
	 * @access private
	 * @param object SearchQueryTerm
	 * @return string
	 */
	private function _getSqlFromTerm($term, $andor)
	{
		// Initiate return value
		$sql = null;

		// Check for locale first
		if ($term->attribute == 'locale')
		{
			// TODO: exclude locale?
			return $this->_sqlWhere($term->attribute, '=', $term->term);
		}

		// Check for other attributes
		if (!is_null($term->attribute))
		{
			// Is attribute a valid fieldId?
			$fieldId = $this->_getFieldIdFromAttribute($term->attribute);

			$attr = ($fieldId) ? 'fieldId' : 'attribute';
			$val  = ($fieldId) ? $fieldId  : $term->attribute;

			// Use subselect for attributes
			$subSelect = $this->_sqlWhere($attr, '=', $val);
		}
		else
		{
			$subSelect = null;
		}

		// Sanatize term
		if ($keywords = StringHelper::normalizeKeywords($term->term))
		{
			// Create fulltext clause from term
			if ($this->_isFulltextTerm($keywords))
			{
				if ($term->substring)
				{
					$keywords .= '*';
				}

				// Add quotes for exact match
				if (strpos($keywords, ' ') != false)
				{
					$keywords = '"'.$keywords.'"';
				}

				// Determine prefix for ftKeyword
				if ($term->exclude)
				{
					$prefix = '-';
				}
				else if ($andor == 'AND')
				{
					$prefix = '+';
				}
				else
				{
					$prefix = '';
				}

				$keywords = $prefix.$keywords;

				// Might need to check for OR as well:
				// 'salty OR dog' will now effectively be 'salty dog',
				// because it combines FT with non-FT
				if ($subSelect)
				{
					// If there is a subselect, create the MATCH AGAINST bit
					$sql = $this->_sqlMatch($keywords);
				}
				else
				{
					// If there is no subselect, save keyword for later, so it's one big happy query
					$this->_ftKeywords[] = $keywords;
				}
			}

			// Create LIKE clause from term
			else
			{
				// Create LIKE clause from term
				$like = $term->exclude ? 'NOT LIKE' : 'LIKE';
				$keywords = $term->substring ? "% {$keywords}%" : "% {$keywords} %";

				// LIKE clauses don't do grouping of keywords
				$sql = $this->_sqlWhere('keywords', $like, $keywords);
			}

			// If we have a where clause in the subselect, add the keyword bit to it
			if ($subSelect && $sql)
			{
				$sql = $this->_sqlSubSelect($subSelect.' AND '.$sql);
			}
		}

		return $sql;
	}

	/**
	 * Determine if search term is eligable for full-text or not.
	 *
	 * @access private
	 * @param sting $term The search term to check
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
			if (strlen($word) < static::_getMinWordLength() || in_array($word, $ftStopWords))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the fieldId for given attribute or 0 for unmatched.
	 *
	 * @access private
	 * @param string $attribute
	 * @return int
	 */
	private function _getFieldIdFromAttribute($attribute)
	{
		// Create local cache
		static $fields = array();

		if (!$fields)
		{
			// Get all fields
			$allFields = craft()->fields->getAllFields();

			// Store them like id => handle in local cache
			foreach ($allFields AS $field)
			{
				$fields[$field->id] = $field->handle;
			}

			// clean up
			unset($allFields);
		}

		return (($id = array_search($attribute, $fields)) === false) ? 0 : $id;
	}


	/**
	 * Get SQL bit for simple WHERE clause
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
	 * Get SQL but for MATCH AGAINST clause
	 */
	private function _sqlMatch($val, $booleanMode = true)
	{
		return sprintf("MATCH(%s) AGAINST('%s'%s)",
			craft()->db->quoteColumnName('keywords'),
			$val,
			($booleanMode ? ' IN BOOLEAN MODE' : '')
		);
	}

	/**
	 * Get SQL but for MATCH AGAINST clause
	 */
	private function _sqlSubSelect($where)
	{
		return sprintf("%s IN (SELECT %s FROM %s WHERE %s)",
			craft()->db->quoteColumnName('elementId'),
			craft()->db->quoteColumnName('elementId'),
			craft()->db->quoteTableName(DbHelper::addTablePrefix('searchindex')),
			$where
		);
	}
}
