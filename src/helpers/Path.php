<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\helpers;

/**
 * Class Path
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Path
{
    // Public Methods
    // =========================================================================

    /**
     * Ensures that a relative path never goes deeper than its root directory.
     *
     * @param string $path
     *
     * @return boolean
     */
    public static function ensurePathIsContained($path)
    {
        // Sanitize
        $path = StringHelper::convertToUtf8($path);

        $segs = explode('/', $path);
        $level = 0;

        foreach ($segs as $seg) {
            if ($seg === '..') {
                $level--;
            } else if ($seg !== '.') {
                $level++;
            }

            if ($level < 0) {
                return false;
            }
        }

        return true;
    }
}
