<?php
namespace Craft;

/**
 * Search Query Term Group class
 *
 * Contains multiple SearchQueryTerm instances, each representing a term in the search query that was combined by "OR".
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.search
 * @since     1.0
 */
class SearchQueryTermGroup
{
	// Properties
	// =========================================================================

	/**
	 * @var array
	 */
	public $terms;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param array $terms
	 *
	 * @return SearchQueryTermGroup
	 */
	public function __construct($terms = array())
	{
		$this->terms = $terms;
	}
}
