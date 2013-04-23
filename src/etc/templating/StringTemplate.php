<?php
namespace Craft;
 
/**
 *
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
