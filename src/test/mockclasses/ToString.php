<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses;

/**
 * Class ToString.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ToString implements \Stringable
{
    /**
     * ToString constructor.
     *
     * @param string $_string
     */
    public function __construct(private string $_string)
    {
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->_string;
    }
}
