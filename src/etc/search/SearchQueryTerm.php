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
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.search
 * @since     1.0
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
