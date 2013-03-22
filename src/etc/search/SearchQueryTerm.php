<?php
namespace Craft;

/**
 * Search Query Term class
 *
 * Represents a term in the search query.
 */
class SearchQueryTerm
{
	public $exclude;
	public $attribute;
	public $term;

	/**
	 * Constructor
	 */
	function __construct($exclude, $attribute, $term)
	{
		$this->exclude   = $exclude;
		$this->attribute = $attribute;
		$this->term      = $term;
	}
}
