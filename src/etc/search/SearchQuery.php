<?php
namespace Craft;

/**
 * Search Query class
 */
class SearchQuery
{
	private $_query;
	private $_tokens;

	/**
	 * Constructor
	 *
	 * @param string $query
	 */
	function __construct($query)
	{
		$this->_query = $query;
		$this->_tokens = array();
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
	 * Returns the given query.
	 *
	 * @return string
	 */
	public function getQuery()
	{
		return $this->_query;
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
}
