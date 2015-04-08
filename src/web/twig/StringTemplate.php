<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig;

/**
 * Class StringTemplate
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class StringTemplate
{
	// Properties
	// =========================================================================

	/**
	 * @var null|string
	 */
	public $cacheKey;

	/**
	 * @var null|string
	 */
	public $template;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param string $cacheKey
	 * @param string $template
	 *
	 * @return StringTemplate
	 */
	public function __construct($cacheKey = null, $template = null)
	{
		$this->cacheKey = $cacheKey;
		$this->template = $template;
	}

	/**
	 * Use the cache key as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->cacheKey;
	}
}
