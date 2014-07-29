<?php
namespace Craft;

/**
 * Class StringTemplate
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.templating
 * @since     1.0
 */
class StringTemplate
{
	public $cacheKey;
	public $template;
 
	/**
	 * Constructor
	 *
	 * @param string $cacheKey
	 * @param string $template
	 */
	function __construct($cacheKey = null, $template = null)
	{
		$this->cacheKey = $cacheKey;
		$this->template = $template;
	}
 
	/**
	 * Use the cache key as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->cacheKey;
	}
}
