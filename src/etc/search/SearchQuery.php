<?php
namespace Craft;

/**
 * Search Query class
 */
class SearchQuery
{
	const FT_MIN_WORD_LENGTH = 4;

	private static $_ftStopWords;

	private $_query;
	private $_tokens;
	private $_fulltext;

	/**
	 * Returns the FULLTEXT stop words.
	 *
	 * @static
	 * @access private
	 * @return array
	 */
	private static function _getFulltextStopWords()
	{
		if (!isset(static::$_ftStopWords))
		{
			static::$_ftStopWords = array_map('StringHelper::normalizeKeywords',
				array("it's", 'able', 'about', 'above', 'according')
			);
		}

		return static::$_ftStopWords;
	}

	/**
	 * Constructor
	 *
	 * @param string $query
	 */
	function __construct($query)
	{
		$this->_query = $query;
		$this->_tokens = array();
		$this->_fulltext = true;
		$this->_parse();
	}

	/**
	 * Returns the tokens.
	 *
	 * @return array
	 */
	public function getTokens()
	{
		return $this->_tokens;
	}

	/**
	 * Returns the fulltext boolean.
	 *
	 * @return bool
	 */
	public function isFulltext()
	{
		return $this->_fulltext;
	}

	/**
	 * Get sql for current search query.
	 *
	 * @return string
	 */
	public function getSql()
	{
		return $this->isFulltext() ? '' : $this->_getFallbackSql();
	}

	/**
	 * Parses the query into an array of tokens.
	 *
	 * @access private
	 */
	private function _parse()
	{
		for ($token = strtok($this->_query, ' '); $token !== false; $token = strtok(' '))
		{
			$appendToPrevious = false;

			if ($token == 'OR')
			{
				// Grab the next one or bail
				if (($token = strtok(' ')) === false)
				{
					break;
				}

				$totalTokens = count($this->_tokens);

				// I suppose it's possible the query started with "OR"
				if ($totalTokens)
				{
					// Set the previous token to a TermGroup, if it's not already
					$previousToken = $this->_tokens[$totalTokens-1];

					if (!($previousToken instanceof SearchQueryTermGroup))
					{
						$previousToken = new SearchQueryTermGroup(array($previousToken));
						$this->_tokens[$totalTokens-1] = $previousToken;
					}

					$appendToPrevious = true;
				}
			}

			// Is this an exclude term?
			if ($exclude = ($token[0] == '-'))
			{
				$token = substr($token, 1);

				if (!$token)
				{
					continue;
				}
			}

			// Is this an attribute-specific term?
			if (preg_match('/^(\w+):(.+)$/', $token, $match))
			{
				$attribute = $match[1];
				$token = $match[2];
			}
			else
			{
				$attribute = null;
			}

			// Does it start with a quote?
			if (strpos('"\'', $token[0]) !== false)
			{
				// Is the end quote at the end of this very token?
				if ($token[strlen($token)-1] == $token[0])
				{
					$term = substr($token, 1, -1);
				}
				else
				{
					$term = substr($token, 1).' '.strtok($token[0]);
				}
			}
			else
			{
				$term = $token;
			}

			// Clean up the final token, strip out ignore words
			$term = StringHelper::normalizeKeywords($term, craft()->config->get('searchIgnoreWords'));

			// Skip if cleaning did returned nothing
			if (!$term)
			{
				continue;
			}

			// Check if the term is okay for full-text
			if ($this->_fulltext && !$this->_isFulltextTerm($term))
			{
				$this->_fulltext = false;
			}

			$term = new SearchQueryTerm($exclude, $attribute, $term);

			if ($appendToPrevious)
			{
				$previousToken->terms[] = $term;
			}
			else
			{
				$this->_tokens[] = $term;
			}
		}
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
		$ftStopWords = static::_getFulltextStopWords();

		// Split the term into individual words
		$words = explode(' ', $term);

		// Then loop through terms and return false it doesn't match up
		foreach ($words as $word)
		{
			if (strlen($word) < static::FT_MIN_WORD_LENGTH || in_array($word, $ftStopWords))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Generates a piece of WHERE clause for fallback (LIKE) search from search term
	 *
	 * @access private
	 * @param object SearchQueryTerm
	 * @return string
	 */
	private function _getFallbackSqlFromTerm($term)
	{
		$sqlTmpl = "(`%s` %s '%s')";

		$like = $term->exclude ? 'NOT LIKE' : 'LIKE';
		$eq   = $term->exclude ? '!=' : '=';

		if ($term->attribute == 'locale')
		{
			return sprintf($sqlTmpl, $term->attribute, $eq, $term->term);
		}
		else if ($fieldId == '0') // TODO: get the fieldID from thing
		{
			$and = sprintf($sqlTmpl, 'fieldId', '=', $fieldId);
		}
		else if (!empty($term->attribute))
		{
			$and = sprintf($sqlTmpl, 'attribute', '=', $term->attribute);
		}
		else
		{
			$and = null;
		}

		$sql = sprintf($sqlTmpl, 'keywords', $like, "% {$term->term} %");

		if (!empty($and))
		{
			$sql = "({$sql} AND {$and})";
		}

		return $sql;
	}

	/**
	 * Generates complete WHERE clause for fallback (LIKE) search from given tokens.
	 *
	 * @access private
	 * @param array $tokens
	 * @param string $glue
	 */
	private function _getFallbackSql($tokens = array(), $glue = 'AND')
	{
		if (!$tokens)
		{
			$tokens = $this->_tokens;
		}

		$sql = array();

		foreach ($tokens AS $obj)
		{
			if ($obj instanceof SearchQueryTermGroup)
			{
				$sql[] = $this->_getFallbackSql($obj->terms, 'OR');
			}
			else if ($obj instanceof SearchQueryTerm)
			{
				$sql[] = $this->_getFallbackSqlFromTerm($obj);
			}
		}

		return implode(" {$glue} ", $sql);
	}
}
