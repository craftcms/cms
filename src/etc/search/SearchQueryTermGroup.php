<?php
namespace Craft;

/**
 * Search Query Term Group class
 *
 * Contains multiple SearchQueryTerm instances, each representing a term in the search query that was combined by "OR".
 *
 * @package craft.app.etc.search
 */
class SearchQueryTermGroup
{
	public $terms;

	/**
	 * Constructor
	 */
	function __construct($terms = array())
	{
		$this->terms = $terms;
	}
}
