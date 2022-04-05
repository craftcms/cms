<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses;

/**
 * Class ToString.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.2
 */
class ToString
{
    /**
     * @var string
     */
    private $_string;

    /**
     * ToString constructor.
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
