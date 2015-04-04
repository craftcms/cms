<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\search;

/**
 * Search Query Term class
 *
 * Represents a term in the search query.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SearchQueryTerm
{
	// Properties
	// =========================================================================

	/**
	 * @var bool
	 */
	public $exclude = false;

	/**
	 * @var bool
	 */
	public $exact = false;

	/**
	 * @var bool
	 */
	public $subLeft = false;

	/**
	 * @var bool
	 */
	public $subRight = false;

	/**
	 * @var null
	 */
	public $attribute = null;

	/**
	 * @var null
	 */
	public $term = null;
}
