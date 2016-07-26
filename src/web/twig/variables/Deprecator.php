<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

/**
 * Class Deprecator variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Deprecator
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the total number of deprecation errors that have been logged.
     *
     * @return integer
     */
    public function getTotalLogs()
    {
        return \Craft::$app->getDeprecator()->getTotalLogs();
    }
}
