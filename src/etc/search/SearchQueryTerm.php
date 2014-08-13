<?php
namespace Craft;

/**
 * Search Query Term class
 *
 * Represents a term in the search query.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.search
 * @since     1.0
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
