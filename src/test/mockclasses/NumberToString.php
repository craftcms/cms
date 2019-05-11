<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses;

/**
 * Class NumberToString.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.1
 */
class NumberToString
{
    // Private Properties
    // =========================================================================

    /**
     * @var string
     */
    private $_string;

    // Public Methods
    // =========================================================================

    /**
     * NumberToString constructor.
     *
     * @param string $string
     */
    public function __construct(string $string)
    {
        $this->_string = $string;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->_string;
    }
}