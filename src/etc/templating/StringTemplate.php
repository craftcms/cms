<?php
namespace Craft;

/**
 * Class StringTemplate
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.templating
 * @since     1.0
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
