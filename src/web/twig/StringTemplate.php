<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig;

/**
 * Class StringTemplate
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
