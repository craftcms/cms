<?php
namespace Craft;

/**
 * Search Query Term class
 *
 * Represents a term in the search query.
 *
 * @package craft.app.etc.search
 */
class SearchQueryTerm
{
	public $exclude   = false;
	public $exact     = false;
	public $subLeft   = false;
	public $subRight  = false;
	public $attribute = null;
	public $term      = null;
}
