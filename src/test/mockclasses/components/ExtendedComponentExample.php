<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses\components;

/**
 * Class ExtendedComponentExample.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.2
 */
class ExtendedComponentExample extends ComponentExample
{
    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public static function displayName(): string
    {
        return 'Extended component example';
    }
}
