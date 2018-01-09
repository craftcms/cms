<?php
namespace Craft;

/**
 * Search Query class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.search
 * @since     1.0
 */
class SearchQuery
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	private $_query;

	/**
	 * @var array
	 */
	private $_termOptions;

	/**
	 * @var array
	 */
	private $_tokens;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param string $query
	 * @param array $termOptions
	 *
	 * @return SearchQuery
	 */
	public function __construct($query, $termOptions = array())
	{
		$this->_query = $query;
		$this->_termOptions = $termOptions;
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

	// Private Methods
	// =========================================================================

	/**
	 * Parses the query into an array of tokens.
	 *
	 * @return null
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

			$term = new SearchQueryTerm();

			// Set the default options
			foreach ($this->_termOptions as $option => $value)
			{
				$term->$option = $value;
			}

			// Is this an exclude term?
			if (StringHelper::getCharAt($token, 0) == '-')
			{
				$term->exclude = true;
				$token = mb_substr($token, 1);
			}

			// Is this an attribute-specific term?
			if (preg_match('/^(\w+)(::?)(.+)$/', $token, $match))
			{
				$term->attribute = $match[1];
				$token = $match[3];

				if ($match[2] == '::')
				{
					$term->exact = true;
				}
			}

			// Does it start with a quote?
			if ($token && mb_strpos('"\'', StringHelper::getCharAt($token, 0)) !== false)
			{
				// Is the end quote at the end of this very token?
				if (StringHelper::getCharAt($token, mb_strlen($token)-1) == StringHelper::getCharAt($token, 0))
				{
					$token = mb_substr($token, 1, -1);
				}
				else
				{
					$token = mb_substr($token, 1).' '.strtok(StringHelper::getCharAt($token, 0));
				}
			}

			// Include sub-word matches?
			if ($token && StringHelper::getCharAt($token, 0) == '*')
			{
				$term->subLeft = true;
				$token = mb_substr($token, 1);
			}

			if ($token)
			{
				if (substr($token, -1) == '*')
				{
					$term->subRight = true;
					$token = mb_substr($token, 0, -1);
				}
			}
			else
			{
				// subRight messes `attr:*` queries up
				$term->subRight = false;
			}

			$term->term = $token;

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
