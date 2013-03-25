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
	public $substring;
	public $attribute;
	public $term;

	/**
	 * Constructor
	 */
	function __construct($exclude, $substring, $attribute, $term)
	{
		$this->exclude   = $exclude;
		$this->substring = $substring;
		$this->attribute = $attribute;
		$this->term      = $term;
	}
}
